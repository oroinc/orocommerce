<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Model\LineItem;

use Oro\Bundle\ApruveBundle\Apruve\Model\LineItem\ApruveLineItem;
use Oro\Bundle\ApruveBundle\Apruve\Model\LineItem\ApruveLineItemFactory;
use Oro\Bundle\ApruveBundle\Apruve\Provider\SupportedCurrenciesProviderInterface;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Routing\RouterInterface;

class ApruveLineItemFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruveLineItem
     */
    private $apruveLineItem;

    /**
     * @var Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private $product;

    /**
     * @var SupportedCurrenciesProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $supportedCurrenciesProvider;

    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var ApruveLineItemFactory
     */
    private $testedFactory;

    /**
     * @var OrderLineItem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderLineItem;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $priceTotalCents = 10000;
        $priceEaCents = 100;
        $quantity = 100;
        $currency = 'USD';
        $sku = 'sampleSku';
        $title = 'Sample Title';
        $description = 'Sample Description';
        $viewProductUrl = 'http://www.example.com/view/1';
        $productId = 1;

        $this->apruveLineItem = new ApruveLineItem(
            [
                ApruveLineItem::PRICE_TOTAL_CENTS => $priceTotalCents,
                ApruveLineItem::PRICE_EA_CENTS => $priceEaCents,
                ApruveLineItem::QUANTITY => $quantity,
                ApruveLineItem::CURRENCY => $currency,
                ApruveLineItem::SKU => $sku,
                ApruveLineItem::TITLE => $title,
                ApruveLineItem::DESCRIPTION => $description,
                ApruveLineItem::VIEW_PRODUCT_URL => $viewProductUrl,
            ]
        );

        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName', 'getDescription', 'getId'])
        ->getMock();

        $this->product
            ->method('getName')
            ->willReturn($title);

        $this->product
            ->method('getDescription')
            ->willReturn($description);

        $this->product
            ->method('getId')
            ->willReturn($productId);

        /** @var OrderLineItem|\PHPUnit_Framework_MockObject_MockObject $orderLineItem */
        $this->orderLineItem = $this->createMock(OrderLineItem::class);

        $this->orderLineItem
            ->method('getQuantity')
            ->willReturn($quantity);
        $this->orderLineItem
            ->method('getProductSku')
            ->willReturn($sku);
        $this->orderLineItem
            ->method('getParentProduct')
            ->willReturn($this->product);

        $this->supportedCurrenciesProvider = $this
            ->createMock(SupportedCurrenciesProviderInterface::class);
        $this->supportedCurrenciesProvider
            ->method('isSupported')
            ->willReturnMap([
                ['USD', true],
                ['EUR', false],
            ]);

        $this->router = $this->createMock(RouterInterface::class);
        $this->router
            ->method('generate')
            ->with('oro_product_view', ['id' => $productId])
            ->willReturn($viewProductUrl);

        $this->testedFactory = new ApruveLineItemFactory($this->supportedCurrenciesProvider, $this->router);
    }

    /**
     * @dataProvider createFromOrderLineItemDataProvider
     *
     * @param int $price
     * @param int $priceType
     */
    public function testCreateFromOrderLineItem($price, $priceType)
    {
        $this->orderLineItem
            ->method('getCurrency')
            ->willReturn('USD');

        $this->orderLineItem
            ->method('getValue')
            ->willReturn($price);

        $this->orderLineItem
            ->method('getPriceType')
            ->willReturn($priceType);

        $actual = $this->testedFactory->createFromOrderLineItem($this->orderLineItem);

        static::assertEquals($this->apruveLineItem, $actual);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Currency EUR is not supported
     */
    public function testCreateFromOrderLineItemInvalidCurrency()
    {
        $this->orderLineItem
            ->method('getCurrency')
            ->willReturn('EUR');

        $this->testedFactory->createFromOrderLineItem($this->orderLineItem);
    }

    /**
     * @return array
     */
    public function createFromOrderLineItemDataProvider()
    {
        return [
            'with bundled price' => [100, PriceTypeAwareInterface::PRICE_TYPE_BUNDLED],
            'with unit price' => [1, PriceTypeAwareInterface::PRICE_TYPE_UNIT],
        ];
    }
}
