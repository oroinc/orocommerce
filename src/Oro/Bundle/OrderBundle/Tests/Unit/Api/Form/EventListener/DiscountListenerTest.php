<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Api\Form\EventListener;

use Oro\Bundle\OrderBundle\Api\Form\EventListener\DiscountListener;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class DiscountListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TotalHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $totalHelperMock;

    /**
     * @var DiscountListener
     */
    private $listener;

    public function setUp()
    {
        $this->totalHelperMock = $this->createMock(TotalHelper::class);

        $this->listener = new DiscountListener($this->totalHelperMock);
    }

    /**
     * @return FormInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createFormMock()
    {
        return $this->createMock(FormInterface::class);
    }

    /**
     * @return OrderDiscount|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createOrderDiscountMock()
    {
        return $this->createMock(OrderDiscount::class);
    }

    /**
     * @return Order|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createOrderMock()
    {
        return $this->createMock(Order::class);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::SUBMIT => 'onSubmit',
            ],
            DiscountListener::getSubscribedEvents()
        );
    }

    public function testNoOrderField()
    {
        $formMock = $this->createFormMock();
        $orderDiscountMock = $this->createOrderDiscountMock();

        $formMock->expects(self::once())
            ->method('has')
            ->with('order')
            ->willReturn(false);

        $orderDiscountMock->expects(self::never())
            ->method('getOrder');

        $this->totalHelperMock->expects(self::never())
            ->method('fillDiscounts');

        $this->listener->onSubmit(new FormEvent($formMock, $orderDiscountMock));
    }

    public function testOnSubmit()
    {
        $formMock = $this->createFormMock();
        $orderDiscountMock = $this->createOrderDiscountMock();
        $orderMock = $this->createOrderMock();

        $formMock->expects(self::once())
            ->method('has')
            ->with('order')
            ->willReturn(true);

        $orderDiscountMock->expects(self::once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $orderMock->expects(self::once())
            ->method('addDiscount')
            ->with($orderDiscountMock);

        $this->totalHelperMock->expects(self::once())
            ->method('fillDiscounts')
            ->with($orderMock);

        $this->listener->onSubmit(new FormEvent($formMock, $orderDiscountMock));
    }

    public function testWrongData()
    {
        $formMock = $this->createFormMock();

        $formMock->expects(self::once())
            ->method('has')
            ->with('order')
            ->willReturn(true);

        $this->totalHelperMock->expects(self::never())
            ->method('fillDiscounts');

        $this->listener->onSubmit(new FormEvent($formMock, null));
    }

    public function testOrderIsNull()
    {
        $formMock = $this->createFormMock();
        $orderDiscountMock = $this->createOrderDiscountMock();

        $formMock->expects(self::once())
            ->method('has')
            ->with('order')
            ->willReturn(true);

        $orderDiscountMock->expects(self::once())
            ->method('getOrder')
            ->willReturn(null);

        $this->totalHelperMock->expects(self::never())
            ->method('fillDiscounts');

        $this->listener->onSubmit(new FormEvent($formMock, $orderDiscountMock));
    }
}
