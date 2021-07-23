<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderAddressEventListener;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Templating\EngineInterface;

class OrderAddressEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderAddressEventListener */
    protected $listener;

    /** @var EngineInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $twigEngine;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    protected function setUp(): void
    {
        $this->twigEngine = $this->createMock('Symfony\Component\Templating\EngineInterface');
        $this->formFactory = $this->createMock('\Symfony\Component\Form\FormFactoryInterface');

        $this->listener = new OrderAddressEventListener($this->twigEngine, $this->formFactory);
    }

    protected function tearDown(): void
    {
        unset($this->listener, $this->twigEngine, $this->formFactory);
    }

    public function testOnOrderEvent()
    {
        $order = new Order();

        $type = $this->createMock('Symfony\Component\Form\ResolvedFormTypeInterface');
        $type->expects($this->once())->method('getInnerType')->willReturn(new FormType());

        $formConfig = $this->createMock('Symfony\Component\Form\FormConfigInterface');
        $formConfig->expects($this->once())->method('getType')->willReturn($type);
        $formConfig->expects($this->once())->method('getOptions')->willReturn([]);

        /** @var Form|\PHPUnit\Framework\MockObject\MockObject $oldForm */
        $oldForm = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $oldForm->expects($this->any())->method('getName')->willReturn('order');

        $billingAddressField = sprintf('%sAddress', AddressType::TYPE_BILLING);
        $shippingAddressField = sprintf('%sAddress', AddressType::TYPE_SHIPPING);

        $oldForm->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive([$this->equalTo($billingAddressField)], [$this->equalTo($shippingAddressField)])
            ->willReturnOnConsecutiveCalls(true, false);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $field1 */
        $field1 = $this->createMock('Symfony\Component\Form\FormInterface');

        $oldForm->expects($this->once())->method('get')->with($billingAddressField)->willReturn($field1);

        $field1->expects($this->any())->method('getConfig')->willReturn($formConfig);
        $field1->expects($this->any())->method('getName')->willReturn('name');
        $field1->expects($this->any())->method('getData')->willReturn([]);

        $field1View = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $field2 */
        $field2 = $this->createMock('Symfony\Component\Form\FormInterface');

        $field2->expects($this->never())->method('createView');

        $this->twigEngine->expects($this->once())
            ->method('render')
            ->with('OroOrderBundle:Form:customerAddressSelector.html.twig', ['form' => $field1View])
            ->willReturn('view1');

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $field1 */
        $newField1 = $this->createMock('Symfony\Component\Form\FormInterface');
        $newField1->expects($this->once())->method('createView')->willReturn($field1View);
        /** @var Form|\PHPUnit\Framework\MockObject\MockObject $oldForm */
        $newForm = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $builder = $this->createMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())->method('add')->with('billingAddress', FormType::class, $this->isType('array'))
            ->willReturnSelf();
        $builder->expects($this->once())->method('getForm')->willReturn($newForm);
        $this->formFactory->expects($this->once())->method('createNamedBuilder')->willReturn($builder);
        $newForm->expects($this->once())->method('get')->with($billingAddressField)->willReturn($newField1);
        $newForm->expects($this->once())->method('submit')->with($this->isType('array'));

        $event = new OrderEvent($oldForm, $order, ['order' => []]);
        $this->listener->onOrderEvent($event);

        $eventData = $event->getData()->getArrayCopy();

        $this->assertArrayHasKey($billingAddressField, $eventData);
        $this->assertEquals('view1', $eventData[$billingAddressField]);
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
