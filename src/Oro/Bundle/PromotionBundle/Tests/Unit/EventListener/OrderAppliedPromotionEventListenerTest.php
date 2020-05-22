<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\PromotionBundle\EventListener\OrderAppliedPromotionEventListener;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\FormStub;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Templating\EngineInterface;

class OrderAppliedPromotionEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var EngineInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $engine;

    /**
     * @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $formFactory;

    /**
     * @var AppliedPromotionManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $appliedPromotionManager;

    /**
     * @var OrderAppliedPromotionEventListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->engine = $this->createMock(EngineInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->appliedPromotionManager = $this->createMock(AppliedPromotionManager::class);
        $this->listener = new OrderAppliedPromotionEventListener(
            $this->engine,
            $this->formFactory,
            $this->appliedPromotionManager
        );
    }

    public function testOnOrderEventWhenNoAppliedDiscounts()
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
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
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
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
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('has')
            ->with('appliedPromotions')
            ->willReturn(true);

        $order = new Order();
        $formView = new FormView();

        $formType = $this->createMock(ResolvedFormTypeInterface::class);
        $formType->expects($this->once())
            ->method('getInnerType')
            ->willReturn(new FormStub('some name'));
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
            ->with(FormStub::class, $order)
            ->willReturn($newForm);
        $view = 'Some html view';
        $this->engine->expects($this->once())
            ->method('render')
            ->with('OroPromotionBundle:Order:applied_promotions.html.twig', ['form' => $formView])
            ->willReturn($view);

        $this->appliedPromotionManager->expects($this->once())
            ->method('createAppliedPromotions')
            ->with($order);

        $event = new OrderEvent($form, $order, ['some submitted data']);
        $this->listener->onOrderEvent($event);
        $this->assertTrue($event->getData()->offsetExists('appliedPromotions'));
        $this->assertEquals($view, $event->getData()->offsetGet('appliedPromotions'));
    }
}
