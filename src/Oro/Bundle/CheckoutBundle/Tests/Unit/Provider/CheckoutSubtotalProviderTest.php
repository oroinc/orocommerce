<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutSubtotalProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider\AbstractSubtotalProviderTest;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Translation\TranslatorInterface;

class CheckoutSubtotalProviderTest extends AbstractSubtotalProviderTest
{
    use EntityTrait;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $roundingService;

    /** @var ProductPriceProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $productPriceProvider;

    /** @var PriceListTreeHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceListTreeHandler;

    /** @var CheckoutSubtotalProvider */
    protected $provider;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(
                function ($value) {
                    return round($value);
                }
            );

        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->priceListTreeHandler = $this->createMock(PriceListTreeHandler::class);

        $this->provider = new CheckoutSubtotalProvider(
            $this->translator,
            $this->roundingService,
            $this->productPriceProvider,
            $this->priceListTreeHandler,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider)
        );
    }

    public function testGetSubtotalWithoutLineItems()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(CheckoutSubtotalProvider::LABEL)
            ->willReturn('test');

        $entity = new Checkout();
        $entity->setCurrency('USD');

        $subtotal = $this->provider->getSubtotal($entity);
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals(0, $subtotal->getAmount());
        $this->assertFalse($subtotal->isVisible());
    }

    /**
     * @dataProvider getPriceDataProvider
     * @param float $value
     * @param string $identifier
     * @param float $defaultQuantity
     * @param float $quantity
     * @param int $precision
     * @param string $code
     * @param float $expectedValue
     * @param string|null $subtotalCurrency
     */
    public function testGetSubtotalByCurrency(
        $value,
        $identifier,
        $defaultQuantity,
        $quantity,
        $precision,
        $code,
        $expectedValue,
        $subtotalCurrency = null
    ) {
        $currency = 'USD';

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(CheckoutSubtotalProvider::LABEL)
            ->willReturn('test');

        $product = $this->prepareProduct();
        $productUnit = $this->prepareProductUnit($code, $precision);
        $this->preparePrice($value, $identifier, $defaultQuantity);

        $lineItem = new CheckoutLineItem();
        $lineItem->setProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity);

        $entity = new Checkout();
        $entity->addLineItem($lineItem)
            ->setCurrency($currency);

        /** @var CombinedPriceList $priceList */
        $priceList = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $this->priceListTreeHandler->expects($this->exactly($entity->getLineItems()->count()))
            ->method('getPriceList')
            ->with($entity->getCustomer(), $entity->getWebsite())
            ->willReturn($priceList);

        $subtotal = $subtotalCurrency
            ? $this->provider->getSubtotalByCurrency($entity, $subtotalCurrency)
            : $this->provider->getSubtotal($entity);
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($subtotalCurrency ?: $currency, $subtotal->getCurrency());
        $this->assertSame(1, $subtotal->getCombinedPriceList()->getId());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals($expectedValue, $subtotal->getAmount());
        $this->assertTrue($subtotal->isVisible());
    }

    public function testGetName()
    {
        $this->assertEquals(CheckoutSubtotalProvider::NAME, $this->provider->getName());
    }

    public function testIsSupported()
    {
        $entity = new Checkout();
        $this->assertTrue($this->provider->isSupported($entity));
    }

    public function testIsNotSupported()
    {
        $entity = new \stdClass();
        $this->assertFalse($this->provider->isSupported($entity));
    }

    /**
     * @param string $code
     * @param int $precision
     * @return ProductUnit|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareProductUnit($code, $precision)
    {
        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->any())
            ->method('getCode')
            ->willReturn($code);
        $productUnit->expects($this->any())
            ->method('getDefaultPrecision')
            ->willReturn($precision);

        return $productUnit;
    }

    /**
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareProduct()
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        return $product;
    }

    /**
     * @param $value
     * @param $identifier
     * @param $defaultQuantity
     */
    protected function preparePrice($value, $identifier, $defaultQuantity)
    {
        $price = $this->createMock(Price::class);
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
                'value' => 25.2,
                'identifier' => '1-kg-3-USD',
                'defaultQuantity' => 0.5,
                'quantity' => 3,
                'precision' => 0,
                'code' => 'kg',
                'expectedValue' => 151.0,

            ],
            'by currency' => [
                'value' => 142.0,
                'identifier' => '1-item-2-EUR',
                'defaultQuantity' => 1,
                'quantity' => 2,
                'precision' => 0,
                'code' => 'item',
                'expectedValue' => 284,
                'subtotalCurrency' => 'EUR',
            ],
        ];
    }
}
