<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderAddressEventListener;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Twig\Environment;

class OrderAddressEventListenerTest extends \PHPUnit\Framework\TestCase
{
    protected OrderAddressEventListener $listener;

    protected Environment|\PHPUnit\Framework\MockObject\MockObject $twig;

    protected FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $formFactory;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->listener = new OrderAddressEventListener($this->twig, $this->formFactory);
    }

    public function testOnOrderEvent(): void
    {
        $order = new Order();

        $type = $this->createMock(ResolvedFormTypeInterface::class);
        $type->expects(self::once())->method('getInnerType')->willReturn(new FormType());

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects(self::once())->method('getType')->willReturn($type);
        $formConfig->expects(self::once())->method('getOptions')->willReturn([]);

        $oldForm = $this->createMock(Form::class);
        $oldForm->expects(self::any())->method('getName')->willReturn('order');

        $billingAddressField = sprintf('%sAddress', AddressType::TYPE_BILLING);
        $shippingAddressField = sprintf('%sAddress', AddressType::TYPE_SHIPPING);

        $oldForm->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive([$this->equalTo($billingAddressField)], [$this->equalTo($shippingAddressField)])
            ->willReturnOnConsecutiveCalls(true, false);

        $field1 = $this->createMock(FormInterface::class);

        $oldForm->expects(self::once())->method('get')->with($billingAddressField)->willReturn($field1);

        $field1->expects(self::any())->method('getConfig')->willReturn($formConfig);
        $field1->expects(self::any())->method('getName')->willReturn('name');
        $field1->expects(self::any())->method('getData')->willReturn([]);

        $field1View = $this->createMock(FormView::class);

        $field2 = $this->createMock(FormInterface::class);
        $field2->expects(self::never())->method('createView');

        $this->twig->expects(self::once())
            ->method('render')
            ->with('@OroOrder/Form/customerAddressSelector.html.twig', ['form' => $field1View])
            ->willReturn('view1');

        $newField1 = $this->createMock(FormInterface::class);
        $newField1->expects(self::once())->method('createView')->willReturn($field1View);

        $newForm = $this->createMock(Form::class);
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())->method('add')->with('billingAddress', FormType::class, $this->isType('array'))
            ->willReturnSelf();
        $builder->expects(self::once())->method('getForm')->willReturn($newForm);
        $this->formFactory->expects(self::once())->method('createNamedBuilder')->willReturn($builder);
        $newForm->expects(self::once())->method('get')->with($billingAddressField)->willReturn($newField1);
        $newForm->expects(self::once())->method('submit')->with($this->isType('array'));

        $event = new OrderEvent($oldForm, $order, ['order' => []]);
        $this->listener->onOrderEvent($event);

        $eventData = $event->getData()->getArrayCopy();

        self::assertArrayHasKey($billingAddressField, $eventData);
        self::assertEquals('view1', $eventData[$billingAddressField]);
    }

    public function testDoNothingIfNoSubmission(): void
    {
        $event = $this->createMock(OrderEvent::class);
        $event->expects(self::never())
            ->method('getForm');

        $this->listener->onOrderEvent($event);
    }
}
