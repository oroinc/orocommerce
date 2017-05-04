<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Factory\LineItem;

use Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem\ApruveLineItemBuilderFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem\ApruveLineItemBuilderInterface;
use Oro\Bundle\ApruveBundle\Apruve\Factory\LineItem\ApruveLineItemFromPaymentLineItemFactory;
use Oro\Bundle\ApruveBundle\Apruve\Helper\AmountNormalizerInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ApruveLineItemFromPaymentLineItemFactoryTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_ID = 1;
    const AMOUNT = 123.4;
    const QUANTITY = 10;
    const AMOUNT_CENTS = 12340;
    const AMOUNT_EA = '12.34';
    const AMOUNT_EA_CENTS = 1234;
    const PRODUCT_SKU = 'sku1';
    const LINE_ITEM_SKU = 'lineItemSku1';
    const CURRENCY = 'USD';
    const PRODUCT_NAME = 'Sample name';
    const PRODUCT_DESCR = ' Sample description with' . PHP_EOL . 'line breaks and <div>tags</div>';
    const PRODUCT_DESCR_SANITIZED = 'Sample description with line breaks and tags';
    const VIEW_PRODUCT_URL = 'http://example.com/product/view/1';

    /**
     * @var ApruveLineItemBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveLineItemBuilder;

    /**
     * @var ApruveLineItemBuilderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveLineItemBuilderFactory;

    /**
     * @var PaymentLineItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentLineItem;

    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var ApruveLineItemFromPaymentLineItemFactory
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->paymentLineItem = $this->createMock(PaymentLineItemInterface::class);
        $this->apruveLineItemBuilder = $this->createMock(ApruveLineItemBuilderInterface::class);
        $this->apruveLineItemBuilderFactory = $this
            ->createMock(ApruveLineItemBuilderFactoryInterface::class);
        $this->router = $this->createMock(RouterInterface::class);

        $this->factory = new ApruveLineItemFromPaymentLineItemFactory(
            $this->mockAmountNormalizer(),
            $this->apruveLineItemBuilderFactory,
            $this->router
        );
    }

    /**
     * @dataProvider createFromPaymentLineItemDataProvider
     *
     * @param Product|\PHPUnit_Framework_MockObject_MockObject $product
     * @param string                                           $title
     * @param string                                           $lineItemSku
     * @param string                                           $expectedSku
     */
    public function testCreateFromPaymentLineItem($product, $title, $lineItemSku, $expectedSku)
    {
        $this->mockRouter();
        $this->mockPaymentLineItem($product, $lineItemSku);

        $this->apruveLineItemBuilderFactory
            ->expects(static::once())
            ->method('create')
            ->with($title, self::AMOUNT_CENTS, self::QUANTITY, self::CURRENCY)
            ->willReturn($this->apruveLineItemBuilder);

        $this->apruveLineItemBuilder
            ->expects(static::once())
            ->method('setEaCents')
            ->with(self::AMOUNT_EA_CENTS)
            ->willReturnSelf();

        $this->apruveLineItemBuilder
            ->expects(static::once())
            ->method('setSku')
            ->with($expectedSku);

        $this->apruveLineItemBuilder
            ->expects(static::once())
            ->method('setDescription')
            ->with(self::PRODUCT_DESCR_SANITIZED)
            ->willReturnSelf();

        $this->apruveLineItemBuilder
            ->expects(static::once())
            ->method('setViewProductUrl')
            ->with(self::VIEW_PRODUCT_URL);

        $this->apruveLineItemBuilder
            ->expects(static::once())
            ->method('getResult');

        $this->factory->createFromPaymentLineItem($this->paymentLineItem);
    }

    /**
     * @return array
     */
    public function createFromPaymentLineItemDataProvider()
    {
        return [
            'line item sku is not null' => [
                'product' => $this->mockProduct(),
                'title' => self::PRODUCT_NAME,
                'lineItemSku' => self::LINE_ITEM_SKU,
                'expectedSku' => self::LINE_ITEM_SKU,
            ],
            'line item sku is null' => [
                'product' => $this->mockProduct(),
                'title' => self::PRODUCT_NAME,
                'lineItemSku' => null,
                'expectedSku' => self::PRODUCT_SKU,
            ],
        ];
    }

    public function testCreateFromPaymentLineItemIfNoProduct()
    {
        $this->mockPaymentLineItem(null, self::LINE_ITEM_SKU);

        $this->apruveLineItemBuilderFactory
            ->expects(static::once())
            ->method('create')
            ->with(self::LINE_ITEM_SKU, self::AMOUNT_CENTS, self::QUANTITY, self::CURRENCY)
            ->willReturn($this->apruveLineItemBuilder);

        $this->apruveLineItemBuilder
            ->expects(static::once())
            ->method('setEaCents')
            ->with(self::AMOUNT_EA_CENTS)
            ->willReturnSelf();

        $this->apruveLineItemBuilder
            ->expects(static::once())
            ->method('setSku')
            ->with(self::LINE_ITEM_SKU);

        $this->apruveLineItemBuilder
            ->expects(static::never())
            ->method('setDescription')
            ->willReturnSelf();

        $this->apruveLineItemBuilder
            ->expects(static::never())
            ->method('setViewProductUrl');

        $this->apruveLineItemBuilder
            ->expects(static::once())
            ->method('getResult');

        $this->factory->createFromPaymentLineItem($this->paymentLineItem);
    }

    /**
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockProduct()
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getName', 'getDescription', 'getSku'])
            ->getMock();
        $product
            ->method('getId')
            ->willReturn(self::PRODUCT_ID);
        $product
            ->method('getName')
            ->willReturn(self::PRODUCT_NAME);
        $product
            ->method('getDescription')
            ->willReturn(self::PRODUCT_DESCR);
        $product
            ->method('getSku')
            ->willReturn(self::PRODUCT_SKU);

        return $product;
    }

    /**
     * @return AmountNormalizerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockAmountNormalizer()
    {
        $amountNormalizer = $this->createMock(AmountNormalizerInterface::class);
        $amountNormalizer
            ->method('normalize')
            ->willReturnMap([
                [self::AMOUNT, self::AMOUNT_CENTS],
                [self::AMOUNT_EA, self::AMOUNT_EA_CENTS],
            ]);
        return $amountNormalizer;
    }

    private function mockRouter()
    {
        $this->router
            ->method('generate')
            ->with('oro_product_frontend_product_view', ['id' => self::PRODUCT_ID], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn(self::VIEW_PRODUCT_URL);
    }

    /**
     * @param $product
     * @param $lineItemSku
     */
    private function mockPaymentLineItem($product, $lineItemSku)
    {
        $price = $this->createMock(Price::class);
        $price
            ->method('getValue')
            ->willReturn(self::AMOUNT_EA);
        $price
            ->method('getCurrency')
            ->willReturn(self::CURRENCY);

        $this->paymentLineItem
            ->method('getPrice')
            ->willReturn($price);
        $this->paymentLineItem
            ->method('getQuantity')
            ->willReturn(self::QUANTITY);

        $this->paymentLineItem
            ->method('getProductSku')
            ->willReturn($lineItemSku);

        $this->paymentLineItem
            ->method('getProduct')
            ->willReturn($product);
    }
}
