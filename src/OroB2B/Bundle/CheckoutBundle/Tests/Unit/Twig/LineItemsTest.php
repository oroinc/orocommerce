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
        $subtotals = [
            (new Subtotal())->setLabel('label2')->setAmount(321)->setCurrency('UAH'),
            (new Subtotal())->setLabel('label1')->setAmount(123)->setCurrency('USD')
        ];
        $this->totalsProvider->expects($this->once())->method('getSubtotals')->willReturn($subtotals);
        $this->lineItemSubtotalProvider->expects($this->any())->method('getRowTotal')->willReturn($subtotals[0]);
        $order = new Order();

        $currency = 'UAH';
        $quantity = 22;
        $priceValue = 123;
        $name = 'Item Name';
        $product = new Product();
        $order->addLineItem($this->createLineItem($currency, $quantity, $priceValue, $name, $product));

        $result = $this->extension->getOrderLineItems($order);
        $this->assertCount(1, $result['lineItems']);
        $this->assertCount(2, $result['subtotals']);

        $lineItem = $result['lineItems'][0];
        $this->assertEquals($lineItem['product'], $product);
        /** @var Price $price */
        $price = $lineItem['price'];
        $this->assertEquals($price->getValue(), $priceValue);
        $this->assertEquals($price->getCurrency(), $currency);
        $this->assertEquals($lineItem['quantity'], $quantity);

        /** @var Subtotal $subtotal */
        $subtotal = $lineItem['subtotal'];
        $this->assertEquals($subtotal->getAmount(), 321);
        $this->assertEquals($subtotal->getCurrency(), $currency);
        $this->assertNull($lineItem['unit']);

        $firstSubtotal = $result['subtotals'][0];
        $this->assertEquals($firstSubtotal['label'], 'label2');
        /** @var Price $totalPrice */
        $totalPrice = $firstSubtotal['totalPrice'];
        $this->assertEquals($totalPrice->getValue(), '321');
        $this->assertEquals($totalPrice->getCurrency(), 'UAH');
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
        $lineItem->setPrice((new Price())->setCurrency($currency)->setValue($priceValue));
        $lineItem->setProductSku($name);
        $lineItem->setProduct($product);

        return $lineItem;
    }
}
