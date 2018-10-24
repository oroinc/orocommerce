<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityNotPricedStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\LineItemNotPricedStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Translation\TranslatorInterface;

class LineItemNotPricedSubtotalProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyManager;

    /**
     * @var WebsiteCurrencyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteCurrencyProvider;

    /**
     * @var LineItemNotPricedSubtotalProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RoundingServiceInterface
     */
    protected $roundingService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductPriceProvider
     */
    protected $productPriceProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListTreeHandler
     */
    protected $priceListTreeHandler;

    protected function setUp()
    {
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->productPriceProvider = $this->createMock(ProductPriceProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->priceListTreeHandler = $this->createMock(PriceListTreeHandler::class);

        $this->provider = new LineItemNotPricedSubtotalProvider(
            $this->translator,
            $this->roundingService,
            $this->productPriceProvider,
            $this->doctrineHelper,
            $this->priceListTreeHandler,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider)
        );
        $this->provider->setProductClass(Product::class);
        $this->provider->setProductUnitClass(ProductUnit::class);
    }

    /**
     * @dataProvider lineItemsDataProvider
     *
     * @param array $lineItemsData
     * @param float $expectedValue
     * @param int $precision
     */
    public function testGetSubtotal(
        array $lineItemsData,
        $expectedValue,
        $precision
    ) {
        $currency = 'USD';

        $this->roundingService->expects($this->any())
            ->method('round')
            ->will(
                $this->returnCallback(
                    function ($value) use ($precision) {
                        return round($value, $precision, PHP_ROUND_HALF_UP);
                    }
                )
            );
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(LineItemNotPricedSubtotalProvider::NAME . '.label')
            ->willReturn('test');
        /* @var $entityManager EntityManager|\PHPUnit_Framework_MockObject_MockObject */
        $entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->willReturn($entityManager);
        $entityManager->expects($this->any())
            ->method('getReference')
            ->willReturnCallback(
                function ($class, $id) {
                    $field = 'id';
                    if ($class === ProductUnit::class) {
                        $field = 'code';
                    }

                    return $this->getEntity($class, [$field => $id]);
                }
            );

        $prices = [];
        $entity = new EntityNotPricedStub();
        foreach ($lineItemsData as $item) {
            /** @var Product $product */
            $product = $this->getEntity(Product::class, ['id' => $item['product_id']]);
            /** @var ProductUnit $productUnit */
            $productUnit = $this->getEntity(ProductUnit::class, ['code' => $item['unit']]);

            $lineItem = new LineItemNotPricedStub();
            $lineItem->setProduct($product);
            $lineItem->setProductUnit($productUnit);
            $lineItem->setQuantity($item['quantity']);
            $entity->addLineItem($lineItem);

            $prices[$item['identifier']] = Price::create($item['price'], $currency);
        }

        $this->productPriceProvider->expects($this->any())
            ->method('getMatchedPrices')
            ->willReturn($prices);

        $websiteId = 101;
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);
        $this->setValue($website, 'id', $websiteId);
        $entity->setWebsite($website);

        $this->websiteCurrencyProvider->expects($this->once())
            ->method('getWebsiteDefaultCurrency')
            ->with($websiteId)
            ->willReturn($currency);

        /** @var BasePriceList $priceList */
        $priceList = $this->getEntity(BasePriceList::class, ['id' => 1]);

        $this->priceListTreeHandler->expects($this->any())
            ->method('getPriceList')
            ->with($entity->getCustomer(), $website)
            ->willReturn($priceList);

        $subtotal = $this->provider->getSubtotal($entity);
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($currency, $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals($expectedValue, (float)$subtotal->getAmount());
        $this->assertTrue($subtotal->isVisible());
    }

    /**
     * @return array
     */
    public function lineItemsDataProvider()
    {
        return [
            'price with precision 2, system precision 2' => [
                'lineItems' => [
                    [
                        'product_id' => 1,
                        'price' => 0.03,
                        'identifier' => '1-kg-3-USD',
                        'quantity' => 3,
                        'unit' => 'kg'
                    ],
                    [
                        'product_id' => 2,
                        'price' => 1.02,
                        'identifier' => '2-item-7-USD',
                        'quantity' => 7,
                        'unit' => 'item'
                    ]
                ],
                'expectedValue' => 7.23,
                'precision' => 2,
            ],
            'price with precision 4, system precision 2' => [
                'lineItems' => [
                    [
                        'product_id' => 1,
                        'price' => 0.0149,
                        'identifier' => '1-kg-3-USD',
                        'quantity' => 3,
                        'unit' => 'kg'
                    ],
                    [
                        'product_id' => 2,
                        'price' => 1.0149,
                        'identifier' => '2-item-7-USD',
                        'quantity' => 7,
                        'unit' => 'item'
                    ]
                ],
                'expectedValue' => 7.14,
                'precision' => 2,
            ],
            'price with precision 4, system precision 4' => [
                'lineItems' => [
                    [
                        'product_id' => 1,
                        'price' => 0.0149,
                        'identifier' => '1-kg-3-USD',
                        'quantity' => 3,
                        'unit' => 'kg'
                    ],
                    [
                        'product_id' => 2,
                        'price' => 1.0149,
                        'identifier' => '2-item-7-USD',
                        'quantity' => 7,
                        'unit' => 'item'
                    ]
                ],
                'expectedValue' => 7.149,
                'precision' => 4,
            ],
        ];
    }

    public function testGetSubtotalWithoutLineItems()
    {
        $this->roundingService->expects($this->never())
            ->method('round');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(LineItemNotPricedSubtotalProvider::NAME . '.label')
            ->willReturn('test');

        $entity = new EntityNotPricedStub();

        $subtotal = $this->provider->getSubtotal($entity);
        $this->assertInstanceOf(Subtotal::class, $subtotal);
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
}
