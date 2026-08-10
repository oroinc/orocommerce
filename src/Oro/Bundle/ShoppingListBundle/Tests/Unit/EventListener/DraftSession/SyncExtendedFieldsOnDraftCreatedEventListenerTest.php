<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\DraftSession\SyncExtendedFieldsOnDraftCreatedEventListener;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
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
            ShoppingList::class,
            Order::class,
        );
    }

    public function testOnEntityDraftCreatedIgnoresWhenEntityIsNotShoppingList(): void
    {
        $event = new EntityDraftCreatedEvent(
            $this->createMock(EntityDraftAwareInterface::class),
            new Order()
        );

        $this->extendedFieldsProvider->expects(self::never())
            ->method('getApplicableExtendedFields');
        $this->extendedFieldSynchronizer->expects(self::never())
            ->method('synchronize');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedIgnoresWhenDraftIsNotOrder(): void
    {
        $event = new EntityDraftCreatedEvent(
            new ShoppingList(),
            $this->createMock(EntityDraftAwareInterface::class)
        );

        $this->extendedFieldsProvider->expects(self::never())
            ->method('getApplicableExtendedFields');
        $this->extendedFieldSynchronizer->expects(self::never())
            ->method('synchronize');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedSynchronizesApplicableExtendedFields(): void
    {
        $source = new ShoppingList();
        $target = new Order();
        $event = new EntityDraftCreatedEvent($source, $target);

        $this->extendedFieldsProvider->expects(self::exactly(2))
            ->method('getApplicableExtendedFields')
            ->willReturnMap([
                [Order::class, ['field_common' => 'string', 'field_only_target' => 'integer']],
                [ShoppingList::class, ['field_common' => 'string', 'field_only_source' => 'boolean']],
            ]);

        $this->extendedFieldSynchronizer->expects(self::once())
            ->method('synchronize')
            ->with($source, $target, 'field_common', 'string');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedWhenNoApplicableIntersection(): void
    {
        $source = new ShoppingList();
        $target = new Order();
        $event = new EntityDraftCreatedEvent($source, $target);

        $this->extendedFieldsProvider->expects(self::exactly(2))
            ->method('getApplicableExtendedFields')
            ->willReturnMap([
                [Order::class, ['field_only_target' => 'integer']],
                [ShoppingList::class, ['field_only_source' => 'boolean']],
            ]);

        $this->extendedFieldSynchronizer->expects(self::never())
            ->method('synchronize');

        $this->listener->onEntityDraftCreated($event);
    }
}
