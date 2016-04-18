<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Twig;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CheckoutBundle\Twig\LineItems;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

/**
 * @dbIsolation
 */
class LineItemsTest extends WebTestCase
{
    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $totalsProvider;

    /**
     * @var LineItems
     */
    protected $extension;

    public function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $this->totalsProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadOrderItems',
            ]
        );
        $this->extension = new LineItems($this->totalsProvider);
        parent::setUp();
    }

    public function testGetFunctions()
    {
        $functions = [new \Twig_SimpleFunction('order_line_items', [$this->extension, 'getOrderLineItems'])];
        $this->assertEquals($this->extension->getFunctions(), $functions);
    }

    public function testGetOrderLineItems()
    {
        $subtotals = [
            (new Subtotal())->setLabel('label1')->setAmount(123)->setCurrency('USD'),
            (new Subtotal())->setLabel('label2')->setAmount(321)->setCurrency('UAH')
        ];
        $this->totalsProvider->expects($this->once())->method('getSubtotals')->willReturn($subtotals);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $result = $this->extension->getOrderLineItems($order);
        $this->assertCount(2, $result['lineItems']);
        $this->assertCount(2, $result['subtotals']);
        $lineItem = $result['lineItems'][0];
        $this->assertEquals($lineItem['name'], 'simple_order_item_2');
        /** @var Price $price */
        $price = $lineItem['price'];
        $this->assertEquals($price->getValue(), '5.5500');
        $this->assertEquals($price->getCurrency(), 'USD');
        $this->assertEquals($lineItem['quantity'], 6);
        /** @var Price $subtotal */
        $subtotal = $lineItem['subtotal'];
        $this->assertEquals($subtotal->getValue(), '33.3');
        $this->assertEquals($subtotal->getCurrency(), 'USD');
        $this->assertNull($lineItem['unit']);
        $subtotal = $result['subtotals'][0];
        $this->assertEquals($subtotal['label'], 'label1');
        /** @var Price $totalPrice */
        $totalPrice = $subtotal['totalPrice'];
        $this->assertEquals($totalPrice->getValue(), '123');
        $this->assertEquals($totalPrice->getCurrency(), 'USD');
    }
}
