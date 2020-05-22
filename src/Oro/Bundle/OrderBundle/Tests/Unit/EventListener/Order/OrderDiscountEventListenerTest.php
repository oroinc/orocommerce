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

class OrderDiscountEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderAddressEventListener */
    protected $listener;

    /** @var EngineInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $twigEngine;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    protected function setUp(): void
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

        /** @var Form|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = static::createMock(Form::class);
        $form->expects(static::once())
            ->method('has')
            ->with($fieldName)
            ->willReturn(true);
        $form->expects(static::any())->method('getData')->willReturn($order);

        $formView = static::createMock(FormView::class);
        $formView->children = ['discounts' => $this->createMock(FormView::class)];

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
        /** @var OrderEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = static::createMock(OrderEvent::class);
        $event->expects(static::never())
            ->method('getForm');
        $this->listener->onOrderEvent($event);
    }
}
