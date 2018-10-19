<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityNotPricedStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\LineItemNotPricedStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Translation\TranslatorInterface;

class LineItemNotPricedSubtotalProviderTest extends AbstractSubtotalProviderTest
{
    use EntityTrait;

    /**
     * @var LineItemNotPricedSubtotalProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RoundingServiceInterface
     */
    protected $roundingService;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProductPriceProvider
     */
    protected $productPriceProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PriceListTreeHandler
     */
    protected $priceListTreeHandler;

    protected function setUp()
    {
        parent::setUp();
        $this->translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');

        $this->roundingService = $this->createMock('Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface');
        $this->roundingService->expects($this->any())
            ->method('round')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return round($value, 0, PHP_ROUND_HALF_UP);
                    }
                )
            );

        $this->productPriceProvider = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Provider\ProductPriceProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListTreeHandler = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Model\PriceListTreeHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new LineItemNotPricedSubtotalProvider(
            $this->translator,
            $this->roundingService,
            $this->productPriceProvider,
            $this->doctrineHelper,
            $this->priceListTreeHandler,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider)
        );
    }

    protected function tearDown()
    {
        unset($this->translator, $this->provider);
    }

    /**
     * @dataProvider getPriceDataProvider
     * @param float  $value
     * @param string $identifier
     * @param float  $defaultQuantity
     * @param float  $quantity
     * @param float  $expectedValue
     * @param int    $precision
     * @param string $code
     */
    public function testGetSubtotal(
        $value,
        $identifier,
        $defaultQuantity,
        $quantity,
        $expectedValue,
        $precision,
        $code
    ) {
        $currency = 'USD';

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(LineItemNotPricedSubtotalProvider::NAME . '.label')
            ->willReturn('test');

        $product = $this->prepareProduct();
        $productUnit = $this->prepareProductUnit($code, $precision);
        $this->prepareEntityManager($product, $productUnit);
        $this->preparePrice($value, $identifier, $defaultQuantity);

        $entity = new EntityNotPricedStub();
        $lineItem = new LineItemNotPricedStub();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setQuantity($quantity);

        $entity->addLineItem($lineItem);

        $websiteId = 101;
        $website = new Website();
        $this->setValue($website, 'id', $websiteId);
        $entity->setWebsite($website);

        $this->websiteCurrencyProvider->expects($this->once())
            ->method('getWebsiteDefaultCurrency')
            ->with($websiteId)
            ->willReturn($currency);

        /** @var BasePriceList $priceList */
        $priceList = $this->getEntity('Oro\Bundle\PricingBundle\Entity\BasePriceList', ['id' => 1]);

        $this->priceListTreeHandler->expects($this->exactly($entity->getLineItems()->count()))
            ->method('getPriceList')
            ->with($entity->getCustomer(), $website)
            ->willReturn($priceList);

        $subtotal = $this->provider->getSubtotal($entity);
        $this->assertInstanceOf('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($currency, $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals($expectedValue, (float)$subtotal->getAmount());
        $this->assertTrue($subtotal->isVisible());
    }

    public function testGetSubtotalWithoutLineItems()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(LineItemNotPricedSubtotalProvider::NAME . '.label')
            ->willReturn('test');

        $entity = new EntityNotPricedStub();

        $subtotal = $this->provider->getSubtotal($entity);
        $this->assertInstanceOf('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals(0, $subtotal->getAmount());
        $this->assertFalse($subtotal->isVisible());
    }

    public function testGetName()
    {
        $this->assertEquals(LineItemNotPricedSubtotalProvider::NAME, $this->provider->getName());
    }

    public function testIsSupported()
    {
        $entity = new EntityNotPricedStub();
        $this->assertTrue($this->provider->isSupported($entity));
    }

    public function testIsNotSupported()
    {
        $entity = new LineItemNotPricedStub();
        $this->assertFalse($this->provider->isSupported($entity));
    }

    /**
     * @return ProductUnit|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function prepareProductUnit($code, $precision)
    {
        /** @var ProductUnit|\PHPUnit\Framework\MockObject\MockObject $productUnit */
        $productUnit = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\ProductUnit')
            ->disableOriginalConstructor()
            ->getMock();
        $productUnit->expects($this->any())
            ->method('getCode')
            ->willReturn($code);
        $productUnit->expects($this->any())
            ->method('getDefaultPrecision')
            ->willReturn($precision);

        return $productUnit;
    }

    /**
     * @return Product|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function prepareProduct()
    {
        /** @var Product|\PHPUnit\Framework\MockObject\MockObject $product */
        $product = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Product')
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        return $product;
    }

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     */
    protected function prepareEntityManager(Product $product, ProductUnit $productUnit)
    {
        /* @var $entityManager EntityManager|\PHPUnit\Framework\MockObject\MockObject */
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->willReturn($entityManager);
        $entityManager->expects($this->at(0))
            ->method('getReference')
            ->willReturn($product);
        $entityManager->expects($this->at(1))
            ->method('getReference')
            ->willReturn($productUnit);
    }

    /**
     * @param $value
     * @param $identifier
     * @param $defaultQuantity
     */
    protected function preparePrice($value, $identifier, $defaultQuantity)
    {
        /** @var Price|\PHPUnit\Framework\MockObject\MockObject $price */
        $price = $this->getMockBuilder('Oro\Bundle\CurrencyBundle\Entity\Price')
            ->disableOriginalConstructor()
            ->getMock();
        $price->expects($this->any())
            ->method('getValue')
            ->willReturn($value / $defaultQuantity);

        $this->productPriceProvider->expects($this->any())
            ->method('getMatchedPrices')
            ->willReturn([$identifier => $price]);
    }

    /**
     * @return array
     */
    public function getPriceDataProvider()
    {
        return [
            'kilogram' => [
                'value' => 25.0,
                'identifier' => '1-kg-3-USD',
                'defaultQuantity' => 0.5,
                'quantity' => 3,
                'expectedValue' => 150,
                'precision' => 3,
                'code' => 'kg'
            ],
            'item' => [
                'value' => 142.0,
                'identifier' => '1-item-2-USD',
                'defaultQuantity' => 1,
                'quantity' => 2,
                'expectedValue' => 284,
                'precision' => 0,
                'code' => 'item'
            ],
        ];
    }
}
