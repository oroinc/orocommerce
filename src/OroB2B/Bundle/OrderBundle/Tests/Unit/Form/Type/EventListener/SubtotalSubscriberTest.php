<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type\EventListener;

use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderDiscount;
use OroB2B\Bundle\OrderBundle\Form\Type\EventListener\SubtotalSubscriber;
use OroB2B\Bundle\OrderBundle\Pricing\PriceMatcher;
use OroB2B\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use OroB2B\Bundle\OrderBundle\Total\TotalHelper;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class SubtotalSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var SubtotalSubscriber */
    protected $subscriber;

    /** @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $totalProvider;

    /** @var LineItemSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $lineItemSubtotalProvider;

    /** @var DiscountSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $discountSubtotalProvider;

    /** @var PriceMatcher|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceMatcher;

    protected function setUp()
    {
        $this->totalProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->lineItemSubtotalProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->discountSubtotalProvider = $this
            ->getMockBuilder('OroB2B\Bundle\OrderBundle\Provider\DiscountSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceMatcher = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Pricing\PriceMatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $totalHelper = new TotalHelper(
            $this->totalProvider,
            $this->lineItemSubtotalProvider,
            $this->discountSubtotalProvider,
            $this->priceMatcher
        );
        $this->subscriber = new SubtotalSubscriber($totalHelper, $this->priceMatcher);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::SUBMIT => 'onSubmitEventListener'
            ],
            SubtotalSubscriber::getSubscribedEvents()
        );
    }

    public function testOnSubmitEventListenerOnNotOrder()
    {
        $event = $this->getMockBuilder(
            'Symfony\Component\Form\FormEvent'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $form->expects($this->never())
            ->method('has');

        $event->expects($this->once())
            ->method('getData');

        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->onSubmitEventListener($event);
    }

    public function testOnSubmitEventListenerOnOrderEmptyTotals()
    {
        $order = $this->prepareOrder();
        $event = $this->prepareEvent($order);

        $this->lineItemSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn(new Subtotal());

        $this->subscriber->onSubmitEventListener($event);
        $this->assertEquals(0, $order->getTotal());
        $this->assertEquals(0, $order->getSubtotal());
        $this->assertEquals(0, $order->getTotalDiscounts()->getValue());
    }

    public function testOnSubmitEventListenerOnOrder()
    {
        $order = $this->prepareOrder();
        $event = $this->prepareEvent($order);
        $this->prepareProviders();

        $this->subscriber->onSubmitEventListener($event);
        $this->assertEquals(90, $order->getTotal());
        $this->assertEquals(42, $order->getSubtotal());
        $this->assertEquals(2, $order->getTotalDiscounts()->getValue());
        $discounts = $order->getDiscounts();
        $this->assertEquals(10.00, $discounts[0]->getPercent());
    }

    public function prepareProviders()
    {
        $subtotal = new Subtotal();
        $subtotalAmount = 42;
        $subtotal->setType(LineItemSubtotalProvider::TYPE);
        $subtotal->setAmount($subtotalAmount);

        $discountSubtotal = new Subtotal();
        $discountSubtotalAmount = 42;
        $discountSubtotal->setType(DiscountSubtotalProvider::TYPE);
        $discountSubtotal->setAmount($discountSubtotalAmount);

        $discountSubtotal2 = new Subtotal();
        $discountSubtotalAmount2 = -40;
        $discountSubtotal2->setType(DiscountSubtotalProvider::TYPE);
        $discountSubtotal2->setAmount($discountSubtotalAmount2);

        $total = new Subtotal();
        $totalAmount = 90;
        $total->setType(TotalProcessorProvider::TYPE);
        $total->setAmount($totalAmount);

        $this->lineItemSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $this->discountSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn([$discountSubtotal, $discountSubtotal2]);

        $this->priceMatcher->expects($this->any())->method('addMatchingPrices');

        $this->totalProvider->expects($this->any())
            ->method('getTotal')
            ->willReturn($total);
    }

    /**
     * @return Order
     */
    protected function prepareOrder()
    {
        $order = new Order();
        $discount1 = new OrderDiscount();
        $discount1->setType(OrderDiscount::TYPE_AMOUNT);
        $discount1->setAmount(4.2);
        $order->addDiscount($discount1);

        return $order;
    }

    /**
     * @param $order
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Form\FormEvent
     */
    protected function prepareEvent($order)
    {
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $form->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getData')
            ->willReturn($order);

        $event->expects($this->once())
            ->method('setData');

        $subForm = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $subForm->expects($this->once())
            ->method('submit')
            ->willReturnSelf();

        $form->expects($this->once())
            ->method('get')
            ->willReturn($subForm);

        return $event;
    }
}
