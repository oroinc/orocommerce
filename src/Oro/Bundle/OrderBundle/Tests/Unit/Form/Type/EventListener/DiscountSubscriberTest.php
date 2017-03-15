<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\DiscountSubscriber;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DiscountSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TotalHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $totalHelperMock;

    /**
     * @var DiscountSubscriber
     */
    private $testedProcessor;

    public function setUp()
    {
        $this->totalHelperMock = $this->createMock(TotalHelper::class);

        $this->testedProcessor = new DiscountSubscriber($this->totalHelperMock);
    }

    /**
     * @return FormEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createEventMock()
    {
        return $this->createMock(FormEvent::class);
    }

    /**
     * @return OrderDiscount|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createOrderDiscountMock()
    {
        return $this->createMock(OrderDiscount::class);
    }

    /**
     * @return Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createOrderMock()
    {
        return $this->createMock(Order::class);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::SUBMIT => 'onSubmitEventListener',
            ],
            DiscountSubscriber::getSubscribedEvents()
        );
    }

    public function testOnSubmitEventListener()
    {
        $eventMock = $this->createEventMock();
        $orderDiscountMock = $this->createOrderDiscountMock();
        $orderMock = $this->createOrderMock();

        $eventMock
            ->expects(static::once())
            ->method('getData')
            ->willReturn($orderDiscountMock);

        $orderDiscountMock
            ->expects(static::once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $orderMock
            ->expects(static::once())
            ->method('addDiscount')
            ->with($orderDiscountMock);

        $this->totalHelperMock
            ->expects(static::once())
            ->method('fillDiscounts')
            ->with($orderMock);

        $this->testedProcessor->onSubmitEventListener($eventMock);
    }

    public function testWrongData()
    {
        $eventMock = $this->createEventMock();

        $eventMock
            ->expects(static::once())
            ->method('getData')
            ->willReturn(null);

        $this->totalHelperMock
            ->expects(static::never())
            ->method('fillDiscounts');

        $this->testedProcessor->onSubmitEventListener($eventMock);
    }
}
