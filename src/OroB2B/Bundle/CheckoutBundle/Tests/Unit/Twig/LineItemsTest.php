<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Twig;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\Twig\LineItemsExtension;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class LineItemsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $totalsProvider;

    /**
     * @var LineItemSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $lineItemSubtotalProvider;

    /**
     * @var LineItemsExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->totalsProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->lineItemSubtotalProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new LineItemsExtension($this->totalsProvider, $this->lineItemSubtotalProvider);
    }

    public function testGetFunctions()
    {
        $functions = [new \Twig_SimpleFunction('order_line_items', [$this->extension, 'getOrderLineItems'])];
        $this->assertEquals($this->extension->getFunctions(), $functions);
    }

    public function testGetOrderLineItems()
    {
        $currency = 'UAH';
        $quantity = 22;
        $priceValue = 123;
        $name = 'Item Name';

        $subtotals = [
            (new Subtotal())->setLabel('label2')->setAmount(321)->setCurrency('UAH'),
            (new Subtotal())->setLabel('label1')->setAmount(123)->setCurrency('USD')
        ];
        $this->totalsProvider->expects($this->once())->method('getSubtotals')->willReturn($subtotals);
        $this->lineItemSubtotalProvider->expects($this->any())->method('getRowTotal')->willReturn(321);
        $order = new Order();
        $order->setCurrency($currency);

        $product = new Product();
        $order->addLineItem($this->createLineItem($currency, $quantity, $priceValue, $name, $product));

        $result = $this->extension->getOrderLineItems($order);
        $this->assertArrayHasKey('lineItems', $result);
        $this->assertArrayHasKey('subtotals', $result);
        $this->assertCount(1, $result['lineItems']);
        $this->assertCount(2, $result['subtotals']);

        $lineItem = $result['lineItems'][0];
        $this->assertEquals($product, $lineItem['product']);
        $this->assertEquals($quantity, $lineItem['quantity']);
        /** @var Price $price */
        $price = $lineItem['price'];
        $this->assertEquals($priceValue, $price->getValue());
        $this->assertEquals($currency, $price->getCurrency());

        /** @var Price $subtotal */
        $subtotal = $lineItem['subtotal'];
        $this->assertEquals(321, $subtotal->getValue());
        $this->assertEquals('UAH', $subtotal->getCurrency());
        $this->assertNull($lineItem['unit']);

        $firstSubtotal = $result['subtotals'][0];
        $this->assertEquals('label2', $firstSubtotal['label']);
        /** @var Price $totalPrice */
        $totalPrice = $firstSubtotal['totalPrice'];
        $this->assertEquals(321, $totalPrice->getValue());
        $this->assertEquals('UAH', $totalPrice->getCurrency());
    }

    /**
     * @param string $currency
     * @param float $quantity
     * @param float $priceValue
     * @param string $name
     * @param Product $product
     * @return OrderLineItem
     */
    protected function createLineItem($currency, $quantity, $priceValue, $name, Product $product)
    {
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency($currency);
        $lineItem->setQuantity($quantity);
        $lineItem->setPrice(Price::create($priceValue, $currency));
        $lineItem->setProductSku($name);
        $lineItem->setProduct($product);

        return $lineItem;
    }
}
