<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderAddressEventListener;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderDiscountEventListener;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Templating\EngineInterface;

class OrderDiscountEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrderAddressEventListener */
    protected $listener;

    /** @var EngineInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $twigEngine;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    protected function setUp()
    {
        $this->twigEngine = $this->createMock(EngineInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->listener = new OrderDiscountEventListener($this->twigEngine, $this->formFactory);
    }

    public function testOnOrderEvent()
    {
        $order = new Order();
        $viewHtml = "any html";
        $fieldName = OrderType::DISCOUNTS_FIELD_NAME;

        /** @var Form|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = static::createMock(Form::class);
        $form->expects(static::once())
            ->method('has')
            ->with($fieldName)
            ->willReturn(true);
        $form->expects(static::any())->method('getData')->willReturn($order);

        $formView = static::createMock(FormView::class);
        $this->twigEngine->expects(static::once())
            ->method('render')
            ->with(OrderDiscountEventListener::TEMPLATE, ['form' => $formView])
            ->willReturn($viewHtml);
        $form->expects(static::once())->method('createView')->willReturn($formView);

        $event = new OrderEvent($form, $order, ['order' => []]);
        $this->listener->onOrderEvent($event);

        $eventData = $event->getData()->getArrayCopy();

        static::assertArrayHasKey($fieldName, $eventData);
        static::assertEquals($viewHtml, $eventData[$fieldName]);
    }

    public function testDoNothingIfNoSubmission()
    {
        /** @var OrderEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = static::createMock(OrderEvent::class);
        $event->expects(static::never())
              ->method('getForm');
        $this->listener->onOrderEvent($event);
    }
}
