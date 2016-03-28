<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Total;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use OroB2B\Bundle\OrderBundle\Total\TotalHelper;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class TotalHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var  TotalHelper */
    protected $helper;

    /** @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $totalProvider;

    /** @var LineItemSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $lineItemSubtotalProvider;

    /** @var DiscountSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $discountSubtotalProvider;

    protected function setUp()
    {
        $this->totalProvider = $this->getMockBuilder(
            'OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->lineItemSubtotalProvider = $this->getMockBuilder(
            'OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->discountSubtotalProvider = $this->getMockBuilder(
            'OroB2B\Bundle\OrderBundle\Provider\DiscountSubtotalProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new TotalHelper(
            $this->totalProvider,
            $this->lineItemSubtotalProvider,
            $this->discountSubtotalProvider
        );
    }

    public function testFillSubtotals()
    {
        $subtotal = new Subtotal();
        $subtotalAmount = 42;
        $subtotal->setType(LineItemSubtotalProvider::TYPE);
        $subtotal->setAmount($subtotalAmount);

        $this->lineItemSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $order = new Order();
        $this->helper->fillSubtotals($order);

        $this->assertEquals(42, $order->getSubtotal());
    }

    public function testFillDiscounts()
    {
        $discountSubtotal = new Subtotal();
        $discountSubtotalAmount = 42;
        $discountSubtotal->setType(DiscountSubtotalProvider::TYPE);
        $discountSubtotal->setAmount($discountSubtotalAmount);

        $discountSubtotal2 = new Subtotal();
        $discountSubtotalAmount2 = -40;
        $discountSubtotal2->setType(DiscountSubtotalProvider::TYPE);
        $discountSubtotal2->setAmount($discountSubtotalAmount2);

        $this->discountSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn([$discountSubtotal, $discountSubtotal2]);

        $order = new Order();
        $this->helper->fillDiscounts($order);
        $this->assertEquals(2, $order->getTotalDiscounts()->getValue());
    }

    public function testFillTotal()
    {
        $total = new Subtotal();
        $totalAmount = 90;
        $total->setType(TotalProcessorProvider::TYPE);
        $total->setAmount($totalAmount);

        $this->totalProvider->expects($this->any())
            ->method('getTotal')
            ->willReturn($total);

        $order = new Order();
        $this->helper->fillTotal($order);

        $this->assertEquals($totalAmount, $order->getTotal());
    }
}
