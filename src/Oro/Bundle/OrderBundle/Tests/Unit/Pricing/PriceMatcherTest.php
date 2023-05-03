<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Pricing;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactory;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceMatcherTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var MatchingPriceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var ProductPriceScopeCriteriaFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $priceScopeCriteriaFactory;

    /** @var PriceMatcher */
    private $matcher;

    private ProductPriceCriteriaFactory $productPriceCriteriaFactory;

    private OrderLineItem $orderLineItem;

    private Order $order;

    private ProductPriceCriteria $productPriceCriteria;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(MatchingPriceProvider::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->productPriceCriteriaFactory = $this->createMock(ProductPriceCriteriaFactory::class);

        $this->order = $this->createMock(Order::class);
        $this->orderLineItem = $this->createMock(OrderLineItem::class);
        $this->productPriceCriteria  = $this->createMock(ProductPriceCriteria::class);

        $this->matcher = new PriceMatcher(
            $this->provider,
            $this->priceScopeCriteriaFactory,
            $this->productPriceCriteriaFactory
        );
    }

    public function testGetMatchingPrices()
    {
        $lineItemQuantity = 5;
        $productUnitCode = 'code';
        $lineItemCurrency = 'USD';
        $orderCurrency = 'EUR';

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productUnit = new ProductUnit();
        $productUnit->setCode($productUnitCode);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setQuantity($lineItemQuantity);
        $lineItem->setCurrency($lineItemCurrency);
        $lineItem->setProductUnit($productUnit);

        $product2 = new Product();
        $lineItem2 = new OrderLineItem();
        $lineItem2->setQuantity($lineItemQuantity);
        $lineItem2->setProduct($product2);

        $order = new Order();
        $order
            ->setCurrency($orderCurrency)
            ->addLineItem($lineItem)
            ->addLineItem($lineItem2);

        $expectedLineItemsArray = [
            [
                'product' => $product->getId(),
                'unit' => $productUnitCode,
                'qty' => $lineItemQuantity,
                'currency' => $lineItemCurrency,
            ],
            [
                'product' => null,
                'unit' => null,
                'qty' => $lineItemQuantity,
                'currency' => $orderCurrency,
            ],
        ];

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory->expects($this->once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($scopeCriteria);

        $matchedPrices = ['matched', 'prices'];
        $this->provider->expects($this->once())
            ->method('getMatchingPrices')
            ->with($expectedLineItemsArray, $scopeCriteria)
            ->willReturn($matchedPrices);

        $this->matcher->getMatchingPrices($order);
    }

    public function testThatOrderLinesAreFilledWithCurrencyAndValue()
    {
        $identifier = 'identifier';

        $orderLineItems = new ArrayCollection([$this->orderLineItem]);

        $this->order->method('getLineItems')->willReturn($orderLineItems);
        $this->order->method('getCurrency')->willReturn('USD');
        $this->productPriceCriteria->method('getIdentifier')->willReturn($identifier);
        $this->provider->method('getMatchingPrices')->willReturn([
            $identifier => [
                'currency' => 'USD',
                'value' => 123
            ]
        ]);

        $this->productPriceCriteriaFactory->expects($this->once())->method('createListFromProductLineItems')->with(
            $this->equalTo($orderLineItems),
            $this->equalTo('USD')
        )->willReturn([$this->productPriceCriteria]);

        $this->orderLineItem->expects($this->once())->method('setCurrency')->with('USD');
        $this->orderLineItem->expects($this->once())->method('setValue')->with('123');

        $this->matcher->addMatchingPrices($this->order);
    }

    public function testThatOrderLineNotFilledWithValuesWhenProductPriceCriteriaIsNotCreated()
    {
        $identifier = 'identifier';

        $orderLineItems = new ArrayCollection([$this->orderLineItem]);

        $this->order->method('getLineItems')->willReturn($orderLineItems);
        $this->provider->method('getMatchingPrices')->willReturn([
            $identifier => [
                'currency' => 'USD',
                'value' => 123
            ]
        ]);

        $this->productPriceCriteriaFactory->expects($this->once())
            ->method('createListFromProductLineItems')
            ->willReturn([]);
        $this->orderLineItem->expects($this->never())->method('setCurrency');
        $this->orderLineItem->expects($this->never())->method('setValue');

        $this->matcher->addMatchingPrices($this->order);
    }

    public function testThatOrderLineNotFilledWithValuesWhenIdentifiersAreDifferent()
    {
        $identifier1 = 'identifier1';
        $identifier2 = 'identifier2';

        $this->order->method('getLineItems')->willReturn(
            new ArrayCollection([$this->orderLineItem])
        );
        $this->provider->method('getMatchingPrices')->willReturn([
            $identifier1 => []
        ]);
        $this->productPriceCriteria->method('getIdentifier')->willReturn($identifier2);

        $this->productPriceCriteriaFactory->expects($this->once())
            ->method('createListFromProductLineItems')
            ->willReturn([$this->productPriceCriteria]);

        $this->orderLineItem->expects($this->never())->method('setCurrency');
        $this->orderLineItem->expects($this->never())->method('setValue');

        $this->matcher->addMatchingPrices($this->order);
    }
}
