<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\DraftSession;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\DraftSession\SyncOrderToOrderDraftOnOrderEventListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;

final class SyncOrderToOrderDraftOnOrderEventListenerTest extends TestCase
{
    private OrderDraftManager&MockObject $orderDraftManager;

    private SyncOrderToOrderDraftOnOrderEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);

        $this->listener = new SyncOrderToOrderDraftOnOrderEventListener(
            $doctrine,
            $this->orderDraftManager
        );
    }

    public function testOnOrderEventDoesNothingWhenDraftSessionSyncOptionIsDisabled(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('draft_session_sync')
            ->willReturn(false);

        $form
            ->expects(self::never())
            ->method('isSubmitted');

        $event = new OrderEvent($form, new Order());

        $this->orderDraftManager
            ->expects(self::never())
            ->method('hasEntityDraft');

        $this->orderDraftManager
            ->expects(self::never())
            ->method('saveToEntityDraft');

        $this->listener->onOrderEvent($event);
    }

    public function testOnOrderEventDoesNothingWhenFormIsNotSubmitted(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('draft_session_sync')
            ->willReturn(true);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(false);

        $event = new OrderEvent($form, new Order());

        $this->orderDraftManager
            ->expects(self::never())
            ->method('hasEntityDraft');

        $this->orderDraftManager
            ->expects(self::never())
            ->method('saveToEntityDraft');

        $this->listener->onOrderEvent($event);
    }

    public function testOnOrderEventDoesNothingWhenEntityDraftDoesNotExist(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('draft_session_sync')
            ->willReturn(true);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $event = new OrderEvent($form, new Order());

        $this->orderDraftManager
            ->expects(self::once())
            ->method('hasEntityDraft')
            ->with(self::isInstanceOf(Order::class))
            ->willReturn(false);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('saveToEntityDraft');

        $this->listener->onOrderEvent($event);
    }

    public function testOnOrderEventDoesNothingWhenOrderIsNotOrderInstance(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('draft_session_sync')
            ->willReturn(true);

        $event = $this->createMock(OrderEvent::class);
        $event
            ->expects(self::once())
            ->method('getForm')
            ->willReturn($form);
        $event
            ->expects(self::once())
            ->method('getOrder')
            ->willReturn(null);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('hasEntityDraft');

        $this->orderDraftManager
            ->expects(self::never())
            ->method('saveToEntityDraft');

        $this->listener->onOrderEvent($event);
    }

    public function testOnOrderEventSavesEntityDraftWhenDraftExists(): void
    {
        $order = new Order();
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('draft_session_sync')
            ->willReturn(true);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $event = new OrderEvent($form, $order);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('hasEntityDraft')
            ->with($order)
            ->willReturn(true);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('saveToEntityDraft')
            ->with($order)
            ->willReturn($order);

        $this->listener->onOrderEvent($event);
    }
}
