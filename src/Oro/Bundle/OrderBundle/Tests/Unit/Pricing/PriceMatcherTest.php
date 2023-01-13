<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Pricing;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class PriceMatcherTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var MatchingPriceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var ProductPriceScopeCriteriaFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $priceScopeCriteriaFactory;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var PriceMatcher */
    private $matcher;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(MatchingPriceProvider::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->matcher = new PriceMatcher(
            $this->provider,
            $this->priceScopeCriteriaFactory,
            $this->logger
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

    /**
     * @dataProvider orderEventAddMatchedPricesDataProvider
     */
    public function testAddMatchedPrices(
        array $orderLineItems = [],
        array $matchedPrices = [],
        array $expectedLineItemsPrices = []
    ) {
        $order = new Order();
        $order->setCurrency('USD');

        array_walk(
            $orderLineItems,
            function (OrderLineItem $orderLineItem) use ($order) {
                $order->addLineItem($orderLineItem);
            }
        );

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory->expects($this->once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($scopeCriteria);

        $this->provider->expects($this->once())
            ->method('getMatchingPrices')
            ->with($this->isType('array'), $scopeCriteria)
            ->willReturn($matchedPrices);

        $this->matcher->addMatchingPrices($order);

        foreach ($order->getLineItems() as $key => $orderLineItem) {
            $this->assertArrayHasKey($key, $expectedLineItemsPrices);
            $this->assertEquals($expectedLineItemsPrices[$key], $orderLineItem->getValue());
        }
    }

    public function testFillMatchingPricesWithIncorrectLineItem()
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

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Got error while trying to create new ProductPriceCriteria with message: "{message}"',
                $this->isType('array')
            );

        $matchedPrices = [
            '1-item-10-USD' => [
                'currency' => 'USD',
                'value' => 42
            ]
        ];
        $this->matcher->fillMatchingPrices($order, $matchedPrices);

        $this->assertEquals(42, $lineItem1->getValue());
    }

    /**
     * @dataProvider orderEventAddMatchedPricesDataProvider
     */
    public function testFillMatchedPrices(
        array $orderLineItems = [],
        array $matchedPrices = [],
        array $expectedLineItemsPrices = []
    ) {
        $order = new Order();
        $order->setCurrency('USD');

        array_walk(
            $orderLineItems,
            function (OrderLineItem $orderLineItem) use ($order) {
                $order->addLineItem($orderLineItem);
            }
        );

        $this->provider->expects($this->never())
            ->method('getMatchingPrices');

        $this->matcher->fillMatchingPrices($order, $matchedPrices);

        foreach ($order->getLineItems() as $key => $orderLineItem) {
            $this->assertArrayHasKey($key, $expectedLineItemsPrices);
            $this->assertEquals($expectedLineItemsPrices[$key], $orderLineItem->getValue());
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function orderEventAddMatchedPricesDataProvider(): array
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
