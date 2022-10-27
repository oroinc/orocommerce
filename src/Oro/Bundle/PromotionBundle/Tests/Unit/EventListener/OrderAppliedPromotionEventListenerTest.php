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
use Twig\Environment;

class OrderAppliedPromotionEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $twig;

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
        $this->twig = $this->createMock(Environment::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->appliedPromotionManager = $this->createMock(AppliedPromotionManager::class);
        $this->listener = new OrderAppliedPromotionEventListener(
            $this->twig,
            $this->formFactory,
            $this->appliedPromotionManager
        );
    }

    public function testOnOrderEventWhenNoAppliedDiscounts(): void
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('has')
            ->with('appliedPromotions')
            ->willReturn(false);
        $this->twig->expects(self::never())
            ->method('render');
        $this->formFactory->expects(self::never())
            ->method('create');

        $order = new Order();
        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
        self::assertFalse($event->getData()->offsetExists('appliedPromotions'));
    }

    public function testOnOrderEventWhenNoSubmittedData(): void
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('has')
            ->with('appliedPromotions')
            ->willReturn(true);
        $this->twig->expects(self::never())
            ->method('render');
        $this->formFactory->expects(self::never())
            ->method('create');

        $order = new Order();
        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
        self::assertFalse($event->getData()->offsetExists('appliedPromotions'));
    }

    public function testOnOrderEvent(): void
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('has')
            ->with('appliedPromotions')
            ->willReturn(true);

        $order = new Order();
        $formView = new FormView();

        $formType = $this->createMock(ResolvedFormTypeInterface::class);
        $formType->expects(self::once())
            ->method('getInnerType')
            ->willReturn(new FormStub('some name'));
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects(self::once())
            ->method('getType')
            ->willReturn($formType);
        $form->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $newForm = $this->createMock(FormInterface::class);
        $newForm->expects(self::once())
            ->method('createView')
            ->willReturn($formView);
        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(FormStub::class, $order)
            ->willReturn($newForm);
        $view = 'Some html view';
        $this->twig->expects(self::once())
            ->method('render')
            ->with('@OroPromotion/Order/applied_promotions.html.twig', ['form' => $formView])
            ->willReturn($view);

        $this->appliedPromotionManager->expects(self::once())
            ->method('createAppliedPromotions')
            ->with($order);

        $event = new OrderEvent($form, $order, ['some submitted data']);
        $this->listener->onOrderEvent($event);
        self::assertTrue($event->getData()->offsetExists('appliedPromotions'));
        self::assertEquals($view, $event->getData()->offsetGet('appliedPromotions'));
    }
}
