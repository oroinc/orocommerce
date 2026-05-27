<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\EventListener\DraftSession\SyncExtendedFieldsOnDraftCreatedEventListener;
use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;
use Oro\Component\DraftSession\ExtendedFields\EntityDraftExtendedFieldsProvider;
use Oro\Component\DraftSession\ExtendedFields\EntityDraftExtendedFieldSynchronizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SyncExtendedFieldsOnDraftCreatedEventListenerTest extends TestCase
{
    private EntityDraftExtendedFieldsProvider&MockObject $extendedFieldsProvider;

    private EntityDraftExtendedFieldSynchronizer&MockObject $extendedFieldSynchronizer;

    private SyncExtendedFieldsOnDraftCreatedEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->extendedFieldsProvider = $this->createMock(EntityDraftExtendedFieldsProvider::class);
        $this->extendedFieldSynchronizer = $this->createMock(EntityDraftExtendedFieldSynchronizer::class);

        $this->listener = new SyncExtendedFieldsOnDraftCreatedEventListener(
            $this->extendedFieldsProvider,
            $this->extendedFieldSynchronizer,
            Request::class,
            Order::class,
        );
    }

    public function testOnEntityDraftCreatedIgnoresWhenEntityIsNotRequest(): void
    {
        $event = $this->createMock(EntityDraftCreatedEvent::class);
        $event
            ->method('getEntity')
            ->willReturn(new Order());
        $event
            ->method('getDraft')
            ->willReturn(new Order());

        $this->extendedFieldsProvider
            ->expects(self::never())
            ->method('getApplicableExtendedFields');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedIgnoresWhenDraftIsNotOrder(): void
    {
        $event = $this->createMock(EntityDraftCreatedEvent::class);
        $event
            ->method('getEntity')
            ->willReturn(new Request());
        $event
            ->method('getDraft')
            ->willReturn(new OrderLineItem());

        $this->extendedFieldsProvider
            ->expects(self::never())
            ->method('getApplicableExtendedFields');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedSynchronizesIntersectingExtendedFields(): void
    {
        $request = new Request();
        $orderDraft = new Order();

        $event = $this->createMock(EntityDraftCreatedEvent::class);
        $event
            ->method('getEntity')
            ->willReturn($request);
        $event
            ->method('getDraft')
            ->willReturn($orderDraft);

        $this->extendedFieldsProvider
            ->expects(self::exactly(2))
            ->method('getApplicableExtendedFields')
            ->willReturnMap([
                [Order::class, ['commonField' => 'string', 'orderOnly' => 'int']],
                [Request::class, ['commonField' => 'string', 'requestOnly' => 'text']],
            ]);

        $this->extendedFieldSynchronizer
            ->expects(self::once())
            ->method('synchronize')
            ->with($request, $orderDraft, 'commonField', 'string');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedDoesNothingWhenNoIntersectingFields(): void
    {
        $request = new Request();
        $orderDraft = new Order();

        $event = $this->createMock(EntityDraftCreatedEvent::class);
        $event
            ->method('getEntity')
            ->willReturn($request);
        $event
            ->method('getDraft')
            ->willReturn($orderDraft);

        $this->extendedFieldsProvider
            ->expects(self::exactly(2))
            ->method('getApplicableExtendedFields')
            ->willReturnMap([
                [Order::class, ['orderOnly' => 'int']],
                [Request::class, ['requestOnly' => 'text']],
            ]);

        $this->extendedFieldSynchronizer
            ->expects(self::never())
            ->method('synchronize');

        $this->listener->onEntityDraftCreated($event);
    }
}
