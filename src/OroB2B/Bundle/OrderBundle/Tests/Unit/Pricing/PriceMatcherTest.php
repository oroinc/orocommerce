<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Pricing;

use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Pricing\PriceMatcher;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;
use OroB2B\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceMatcherTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var PriceMatcher
     */
    protected $matcher;

    /** @var MatchingPriceProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var PriceListTreeHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceListTreeHandler;

    protected function setUp()
    {
        $this->provider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\MatchingPriceProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListTreeHandler = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->matcher = new PriceMatcher($this->provider, $this->priceListTreeHandler);
    }

    public function testGetMatchingPrices()
    {
        $lineItemQuantity = 5;
        $productUnitCode = 'code';
        $lineItemCurrency = 'USD';
        $orderCurrency = 'EUR';

        $account = new Account();
        $website = new Website();

        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => 1]);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setQuantity($lineItemQuantity);
        $lineItem->setCurrency($lineItemCurrency);

        $productUnit = new ProductUnit();
        $productUnit->setCode($productUnitCode);
        $lineItem->setProductUnit($productUnit);

        $product2 = new Product();
        $lineItem2 = new OrderLineItem();
        $lineItem2->setQuantity($lineItemQuantity);
        $lineItem2->setProduct($product2);

        $order = new Order();
        $order
            ->setCurrency($orderCurrency)
            ->setAccount($account)
            ->setWebsite($website)
            ->addLineItem($lineItem)
            ->addLineItem($lineItem2);

        $priceList = new BasePriceList();
        $this->priceListTreeHandler
            ->expects($this->once())
            ->method('getPriceList')
            ->with($account, $website)
            ->willReturn($priceList);

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

        $matchedPrices = ['matched', 'prices'];
        $this->provider
            ->expects($this->once())
            ->method('getMatchingPrices')
            ->with($expectedLineItemsArray, $priceList)
            ->willReturn($matchedPrices);

        $this->matcher->getMatchingPrices($order);
    }

    /**
     * @dataProvider orderEventAddMatchedPricesDataProvider
     * @param array $orderLineItems
     * @param array $matchedPrices
     * @param array $expectedLineItemsPrices
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

        /** @var BasePriceList $priceList */
        $priceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\BasePriceList', ['id' => 1]);
        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->willReturn($priceList);

        $this->provider->expects($this->once())
            ->method('getMatchingPrices')
            ->willReturn($matchedPrices);

        $this->matcher->addMatchingPrices($order);

        foreach ($order->getLineItems() as $key => $orderLineItem) {
            $this->assertArrayHasKey($key, $expectedLineItemsPrices);
            $this->assertEquals($expectedLineItemsPrices[$key], $orderLineItem->getValue());
        }
    }

    /**
     * @dataProvider orderEventAddMatchedPricesDataProvider
     * @param array $orderLineItems
     * @param array $matchedPrices
     * @param array $expectedLineItemsPrices
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

        $this->provider->expects($this->never())->method('getMatchingPrices');

        $this->matcher->fillMatchingPrices($order, $matchedPrices);

        foreach ($order->getLineItems() as $key => $orderLineItem) {
            $this->assertArrayHasKey($key, $expectedLineItemsPrices);
            $this->assertEquals($expectedLineItemsPrices[$key], $orderLineItem->getValue());
        }
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function orderEventAddMatchedPricesDataProvider()
    {
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => 1]);
        $invalidProduct = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product');
        $productUnit = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'set']);
        $invalidProductUnit = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit');

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
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
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
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'without productUnit' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setCurrency('USD')
                        ->setQuantity('3'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
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
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'without currency' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProduct($product)
                        ->setQuantity('3'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'invalid currency' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProduct($product)
                        ->setQuantity('3')
                        ->setCurrency(''),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'without quantity' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProduct($product)
                        ->setCurrency('USD'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'invalid quantity #1' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProduct($product)
                        ->setQuantity('string')
                        ->setCurrency('USD'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'invalid quantity #2' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProduct($product)
                        ->setQuantity(-2)
                        ->setCurrency('USD'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
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
