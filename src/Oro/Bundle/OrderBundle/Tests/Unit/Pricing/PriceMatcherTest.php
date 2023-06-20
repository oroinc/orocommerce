<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Pricing;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PriceMatcherTest extends TestCase
{
    use EntityTrait;

    private MatchingPriceProvider|MockObject $provider;

    private ProductPriceScopeCriteriaFactoryInterface|MockObject $priceScopeCriteriaFactory;

    private LoggerInterface|MockObject $logger;

    private PriceMatcher $matcher;

    private ProductPriceCriteriaFactoryInterface|MockObject $productPriceCriteriaFactory;

    private OrderLineItem|MockObject $orderLineItem;

    private Order|MockObject $order;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(MatchingPriceProvider::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->productPriceCriteriaFactory = $this->createMock(ProductPriceCriteriaFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->order = $this->createMock(Order::class);
        $this->orderLineItem = $this->createMock(OrderLineItem::class);
        $this->productPriceCriteria  = $this->createMock(ProductPriceCriteria::class);

        $this->matcher = new PriceMatcher(
            $this->provider,
            $this->priceScopeCriteriaFactory,
            $this->logger
        );

        $this->matcher->setProductPriceCriteriaFactory($this->productPriceCriteriaFactory);
    }

    public function testGetMatchingPrices(): void
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
        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($scopeCriteria);

        $matchedPrices = ['matched', 'prices'];
        $this->provider->expects(self::once())
            ->method('getMatchingPrices')
            ->with($expectedLineItemsArray, $scopeCriteria)
            ->willReturn($matchedPrices);

        $this->matcher->getMatchingPrices($order);
    }

    public function testThatOrderLinesAreFilledWithCurrencyAndValue(): void
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

        $this->productPriceCriteriaFactory->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with(
                self::equalTo([$this->orderLineItem]),
                self::equalTo('USD')
            )
            ->willReturn([$this->productPriceCriteria]);

        $this->orderLineItem->expects(self::once())->method('setCurrency')->with('USD');
        $this->orderLineItem->expects(self::once())->method('setValue')->with('123');

        $this->matcher->addMatchingPrices($this->order);
    }

    public function testThatOrderLineNotFilledWithValuesWhenProductPriceCriteriaIsNotCreated(): void
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

        $this->productPriceCriteriaFactory->expects(self::once())
            ->method('createListFromProductLineItems')
            ->willReturn([]);
        $this->orderLineItem->expects(self::never())->method('setCurrency');
        $this->orderLineItem->expects(self::never())->method('setValue');

        $this->matcher->addMatchingPrices($this->order);
    }

    public function testThatOrderLineNotFilledWithValuesWhenIdentifiersAreDifferent(): void
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

        $this->productPriceCriteriaFactory->expects(self::once())
            ->method('createListFromProductLineItems')
            ->willReturn([$this->productPriceCriteria]);

        $this->orderLineItem->expects(self::never())->method('setCurrency');
        $this->orderLineItem->expects(self::never())->method('setValue');

        $this->matcher->addMatchingPrices($this->order);
    }

    /**
     * @dataProvider orderEventAddMatchedPricesWhenNoProductPriceCriteriaFactoryDataProvider
     */
    public function testAddMatchedPricesWhenNoProductPriceCriteriaFactory(
        array $orderLineItems = [],
        array $matchedPrices = [],
        array $expectedLineItemsPrices = []
    ): void {
        $order = new Order();
        $order->setCurrency('USD');

        array_walk(
            $orderLineItems,
            static function (OrderLineItem $orderLineItem) use ($order) {
                $order->addLineItem($orderLineItem);
            }
        );

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($scopeCriteria);

        $this->provider->expects(self::once())
            ->method('getMatchingPrices')
            ->with(self::isType('array'), $scopeCriteria)
            ->willReturn($matchedPrices);

        $this->matcher->setProductPriceCriteriaFactory(null);
        $this->matcher->addMatchingPrices($order);

        foreach ($order->getLineItems() as $key => $orderLineItem) {
            self::assertArrayHasKey($key, $expectedLineItemsPrices);
            self::assertEquals($expectedLineItemsPrices[$key], $orderLineItem->getValue());
        }
    }

    public function testFillMatchingPricesWithIncorrectLineItemWhenNoProductPriceCriteriaFactory(): void
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $lineItem1 = new OrderLineItem();
        $lineItem1->setProduct($product);
        $lineItem1->setQuantity(10);
        $lineItem1->setCurrency('USD');
        $lineItem1->setProductUnit($productUnit);

        $lineItem2 = new OrderLineItem();
        $lineItem2->setProduct($product);
        $lineItem2->setQuantity(-10);
        $lineItem2->setProductUnit($productUnit);

        $order = new Order();
        $order->setCurrency('USD');
        $order->addLineItem($lineItem1);
        $order->addLineItem($lineItem2);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Got error while trying to create new ProductPriceCriteria with message: "{message}"',
                self::isType('array')
            );

        $matchedPrices = [
            '1-item-10-USD' => [
                'currency' => 'USD',
                'value' => 42
            ]
        ];
        $this->matcher->setProductPriceCriteriaFactory(null);
        $this->matcher->fillMatchingPrices($order, $matchedPrices);

        self::assertEquals(42, $lineItem1->getValue());
    }

    /**
     * @dataProvider orderEventAddMatchedPricesWhenNoProductPriceCriteriaFactoryDataProvider
     */
    public function testFillMatchedPricesWhenNoProductPriceCriteriaFactory(
        array $orderLineItems = [],
        array $matchedPrices = [],
        array $expectedLineItemsPrices = []
    ): void {
        $order = new Order();
        $order->setCurrency('USD');

        array_walk(
            $orderLineItems,
            static function (OrderLineItem $orderLineItem) use ($order) {
                $order->addLineItem($orderLineItem);
            }
        );

        $this->provider->expects(self::never())
            ->method('getMatchingPrices');

        $this->matcher->setProductPriceCriteriaFactory(null);
        $this->matcher->fillMatchingPrices($order, $matchedPrices);

        foreach ($order->getLineItems() as $key => $orderLineItem) {
            self::assertArrayHasKey($key, $expectedLineItemsPrices);
            self::assertEquals($expectedLineItemsPrices[$key], $orderLineItem->getValue());
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function orderEventAddMatchedPricesWhenNoProductPriceCriteriaFactoryDataProvider(): array
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $invalidProduct = $this->getEntity(Product::class);
        $productUnit = $this->getEntity(ProductUnit::class, ['code' => 'set']);
        $invalidProductUnit = $this->getEntity(ProductUnit::class);

        return [
            'empty prices' => [],
            'no matched prices' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setCurrency('USD')
                        ->setQuantity('3'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'without product' => [
                [
                    (new OrderLineItem())
                        ->setProductUnit($productUnit)
                        ->setCurrency('USD')
                        ->setQuantity('3'),
                ],
                ['1-set-3-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'invalid product' => [
                [
                    (new OrderLineItem())
                        ->setProduct($invalidProduct)
                        ->setProductUnit($productUnit)
                        ->setCurrency('USD')
                        ->setQuantity('3'),
                ],
                ['1-set-3-USD' => ['currency' => 'USD', 'value' => 100]],
                [null]
            ],
            'without productUnit' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setCurrency('USD')
                        ->setQuantity('3'),
                ],
                ['1-set-3-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'invalid productUnit' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProductUnit($invalidProductUnit)
                        ->setCurrency('USD')
                        ->setQuantity('3'),
                ],
                ['1-set-3-USD' => ['currency' => 'USD', 'value' => 100]],
                [null]
            ],
            'without currency' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setQuantity('3'),
                ],
                ['1-set-3-USD' => ['currency' => 'USD', 'value' => 100]],
                ['100'],
            ],
            'invalid currency' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setQuantity('3')
                        ->setCurrency(''),
                ],
                ['1-set-3-USD' => ['currency' => 'USD', 'value' => 100]],
                ['100']
            ],
            'without quantity' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setCurrency('USD'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'invalid quantity #1' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setQuantity('string')
                        ->setCurrency('USD'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null]
            ],
            'invalid quantity #2' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setQuantity(-2)
                        ->setCurrency('USD'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null]
            ],
            'one matched price' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setCurrency('USD')
                        ->setQuantity('3'),
                ],
                [
                    '1-set-2-USD' => ['currency' => 'USD', 'value' => 100],
                    '1-set-3-USD' => ['currency' => 'USD', 'value' => 150],
                ],
                ['150'],
            ],
            'currency from order' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setQuantity('3'),
                ],
                [
                    '1-set-2-USD' => ['currency' => 'USD', 'value' => 100],
                    '1-set-3-USD' => ['currency' => 'USD', 'value' => 150],
                ],
                ['150'],
            ],
        ];
    }
}
