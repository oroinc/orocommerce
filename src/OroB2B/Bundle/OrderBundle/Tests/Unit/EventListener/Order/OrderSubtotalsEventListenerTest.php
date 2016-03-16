<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Symfony\Component\Form\FormInterface;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Event\OrderEvent;
use OroB2B\Bundle\OrderBundle\EventListener\Order\OrderSubtotalsEventListener;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class OrderSubtotalsEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use SubtotalTrait;

    /** @var OrderSubtotalsEventListener */
    protected $listener;

    /** @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $totalProcessorProvider;

    protected function setUp()
    {
        $this->totalProcessorProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new OrderSubtotalsEventListener($this->totalProcessorProvider);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->totalProcessorProvider);
    }

    public function testOnOrderEvent()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $order = new Order();

        $subtotals = [
            $this->getSubtotal('type1', 'label1', 100.1, 'USD', true),
            $this->getSubtotal('type2', 'label2', 50, 'EUR', false),
        ];

        $this->totalProcessorProvider->expects($this->once())
            ->method('getSubtotals')
            ->with($order)
            ->willReturn(new ArrayCollection($subtotals));

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);

        $actualData = $event->getData()->getArrayCopy();

        $expectedSubtotals = array_map(
            function (Subtotal $subtotal) {
                return $subtotal->toArray();
            },
            $subtotals
        );

        $this->assertArrayHasKey(OrderSubtotalsEventListener::SUBTOTALS_KEY, $actualData);
        $this->assertEquals($expectedSubtotals, $actualData[OrderSubtotalsEventListener::SUBTOTALS_KEY]);
    }
}
