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
use Twig\Environment;

class OrderDiscountEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var OrderAddressEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->listener = new OrderDiscountEventListener($this->twig, $this->formFactory);
    }

    public function testOnOrderEvent()
    {
        $order = new Order();
        $viewHtml = 'any html';
        $fieldName = OrderType::DISCOUNTS_FIELD_NAME;

        $form = $this->createMock(Form::class);
        $form->expects(self::once())
            ->method('has')
            ->with($fieldName)
            ->willReturn(true);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($order);

        $formView = $this->createMock(FormView::class);
        $formView->children = ['discounts' => $this->createMock(FormView::class)];

        $this->twig->expects(self::once())
            ->method('render')
            ->with(OrderDiscountEventListener::TEMPLATE, ['form' => $formView])
            ->willReturn($viewHtml);
        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $event = new OrderEvent($form, $order, ['order' => []]);
        $this->listener->onOrderEvent($event);

        $eventData = $event->getData()->getArrayCopy();

        self::assertArrayHasKey($fieldName, $eventData);
        self::assertEquals($viewHtml, $eventData[$fieldName]);
    }

    public function testDoNothingIfNoSubmission()
    {
        $event = $this->createMock(OrderEvent::class);
        $event->expects(self::never())
            ->method('getForm');
        $this->listener->onOrderEvent($event);
    }
}
