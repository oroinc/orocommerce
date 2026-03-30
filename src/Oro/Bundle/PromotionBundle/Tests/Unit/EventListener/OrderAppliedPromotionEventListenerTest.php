<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\PromotionBundle\EventListener\OrderAppliedPromotionEventListener;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\FormStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Twig\Environment;

class OrderAppliedPromotionEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private Environment&MockObject $twig;

    private FormFactoryInterface&MockObject $formFactory;

    private AppliedPromotionManager&MockObject $appliedPromotionManager;

    private OrderAppliedPromotionEventListener $listener;

    #[\Override]
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

        $appliedPromotionsView = 'Applied promotions html view';
        $appliedCouponsView = 'Applied coupons html view';
        $this->twig->expects(self::exactly(2))
            ->method('render')
            ->willReturnMap([
                ['@OroPromotion/Order/applied_promotions.html.twig', ['form' => $formView], $appliedPromotionsView],
                ['@OroPromotion/Order/applied_coupons.html.twig', ['form' => $formView], $appliedCouponsView],
            ]);

        $this->appliedPromotionManager->expects(self::once())
            ->method('createAppliedPromotions')
            ->with($order);

        $event = new OrderEvent($form, $order, ['some submitted data']);
        $this->listener->onOrderEvent($event);

        self::assertTrue($event->getData()->offsetExists('appliedPromotions'));
        self::assertEquals($appliedPromotionsView, $event->getData()->offsetGet('appliedPromotions'));
        self::assertTrue($event->getData()->offsetExists('appliedCoupons'));
        self::assertEquals($appliedCouponsView, $event->getData()->offsetGet('appliedCoupons'));
    }
}
