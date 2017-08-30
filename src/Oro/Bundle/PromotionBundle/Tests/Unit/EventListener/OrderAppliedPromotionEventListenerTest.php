<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\PromotionBundle\EventListener\OrderAppliedPromotionEventListener;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Templating\EngineInterface;

class OrderAppliedPromotionEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EngineInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $engine;

    /**
     * @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formFactory;

    /**
     * @var OrderAppliedPromotionEventListener
     */
    private $listener;

    protected function setUp()
    {
        $this->engine = $this->createMock(EngineInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->listener = new OrderAppliedPromotionEventListener($this->engine, $this->formFactory);
    }

    public function testOnOrderEventWhenNoAppliedDiscounts()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('has')
            ->with('appliedPromotions')
            ->willReturn(false);
        $this->engine->expects($this->never())
            ->method('render');
        $this->formFactory->expects($this->never())
            ->method('create');

        $order = new Order();
        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
        $this->assertFalse($event->getData()->offsetExists('appliedPromotions'));
    }

    public function testOnOrderEventWhenNoSubmittedData()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('has')
            ->with('appliedPromotions')
            ->willReturn(true);
        $this->engine->expects($this->never())
            ->method('render');
        $this->formFactory->expects($this->never())
            ->method('create');

        $order = new Order();
        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
        $this->assertFalse($event->getData()->offsetExists('appliedPromotions'));
    }

    public function testOnOrderEvent()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('has')
            ->with('appliedPromotions')
            ->willReturn(true);
        $order = new Order();
        $formView = new FormView();

        $formTypeName = 'some name';
        $formType = $this->createMock(ResolvedFormTypeInterface::class);
        $formType->expects($this->once())
            ->method('getName')
            ->willReturn($formTypeName);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getType')
            ->willReturn($formType);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $newForm = $this->createMock(FormInterface::class);
        $newForm->expects($this->once())
            ->method('createView')
            ->willReturn($formView);
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with($formTypeName, $order)
            ->willReturn($newForm);
        $view = 'Some html view';
        $this->engine->expects($this->once())
            ->method('render')
            ->with('OroPromotionBundle:Order:applied_promotions.html.twig', ['form' => $formView])
            ->willReturn($view);

        $event = new OrderEvent($form, $order, ['some submitted data']);
        $this->listener->onOrderEvent($event);
        $this->assertTrue($event->getData()->offsetExists('appliedPromotions'));
        $this->assertEquals($view, $event->getData()->offsetGet('appliedPromotions'));
    }
}
