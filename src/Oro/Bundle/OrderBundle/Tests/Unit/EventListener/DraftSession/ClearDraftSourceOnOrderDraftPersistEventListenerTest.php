<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\DraftSession\ClearDraftSourceOnOrderDraftPersistEventListener;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\DraftSession\Event\EntityDraftPersistAfterEvent;
use Oro\Component\DraftSession\Event\EntityDraftPersistBeforeEvent;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

final class ClearDraftSourceOnOrderDraftPersistEventListenerTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private ClearDraftSourceOnOrderDraftPersistEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new ClearDraftSourceOnOrderDraftPersistEventListener();
        $this->setUpLoggerMock($this->listener);
    }

    public function testOnEntityDraftPersistBeforeDoesNothingWhenDraftResolvesToNonOrder(): void
    {
        // An OrderLineItem with no associated order resolves to null and is skipped.
        $draft = new OrderLineItem();
        $event = new EntityDraftPersistBeforeEvent($draft, new OrderLineItem());

        $this->listener->onEntityDraftPersistBefore($event);

        // The listener must not mutate the draft when it cannot resolve an Order.
        self::assertNull($draft->getDraftSource());
    }

    public function testOnEntityDraftPersistBeforeClearsOrderAndLineItemDraftSourcesForNewEntities(): void
    {
        $draftSource = new Order();

        $lineItemDraftSource = new OrderLineItem();
        $draftLineItem = new OrderLineItem();
        $draftLineItem->setDraftSource($lineItemDraftSource);

        $draft = new Order();
        $draft->setDraftSource($draftSource);
        $draft->addLineItem($draftLineItem);

        $event = new EntityDraftPersistBeforeEvent($draft, new Order());

        $this->listener->onEntityDraftPersistBefore($event);

        self::assertNull($draft->getDraftSource());
        self::assertNull($draftLineItem->getDraftSource());
    }

    public function testOnEntityDraftPersistBeforeDoesNotClearSourcesForExistingEntities(): void
    {
        $draftSource = new Order();
        ReflectionUtil::setId($draftSource, 100);

        $lineItemDraftSource = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraftSource, 200);

        $draftLineItem = new OrderLineItem();
        $draftLineItem->setDraftSource($lineItemDraftSource);

        $draft = new Order();
        $draft->setDraftSource($draftSource);
        $draft->addLineItem($draftLineItem);

        $event = new EntityDraftPersistBeforeEvent($draft, new Order());

        $this->listener->onEntityDraftPersistBefore($event);

        self::assertSame($draftSource, $draft->getDraftSource());
        self::assertSame($lineItemDraftSource, $draftLineItem->getDraftSource());
    }

    public function testOnEntityDraftPersistBeforeHandlesOrderLineItemDraftByUnwrappingItsOrder(): void
    {
        $draftSource = new Order();

        $lineItemDraftSource = new OrderLineItem();
        $draftLineItem = new OrderLineItem();
        $draftLineItem->setDraftSource($lineItemDraftSource);

        $orderDraft = new Order();
        $orderDraft->setDraftSource($draftSource);
        $orderDraft->addLineItem($draftLineItem);

        // The draft passed to the event is an OrderLineItem, not the Order directly.
        $lineItemDraft = new OrderLineItem();
        $orderDraft->addLineItem($lineItemDraft);

        $event = new EntityDraftPersistBeforeEvent($lineItemDraft, new OrderLineItem());

        $this->listener->onEntityDraftPersistBefore($event);

        self::assertNull($orderDraft->getDraftSource());
        self::assertNull($draftLineItem->getDraftSource());
    }

    public function testOnEntityDraftPersistAfterHandlesOrderLineItemDraftByUnwrappingItsOrder(): void
    {
        $draftSource = new Order();

        $lineItemDraftSource = new OrderLineItem();
        $draftLineItem = new OrderLineItem();
        $draftLineItem->setDraftSource($lineItemDraftSource);

        $orderDraft = new Order();
        $orderDraft->setDraftSource($draftSource);
        $orderDraft->addLineItem($draftLineItem);

        // Drive the before-event first to populate the internal state.
        $lineItemDraft = new OrderLineItem();
        $orderDraft->addLineItem($lineItemDraft);

        $beforeEvent = new EntityDraftPersistBeforeEvent($lineItemDraft, new OrderLineItem());
        $this->listener->onEntityDraftPersistBefore($beforeEvent);

        // Now fire the after-event with the same OrderLineItem draft.
        $afterEvent = new EntityDraftPersistAfterEvent($lineItemDraft, new OrderLineItem());
        $this->listener->onEntityDraftPersistAfter($afterEvent);

        self::assertSame($draftSource, $orderDraft->getDraftSource());
        self::assertSame($lineItemDraftSource, $draftLineItem->getDraftSource());
    }

    public function testOnEntityDraftPersistAfterDoesNothingWhenDraftResolvesToNonOrder(): void
    {
        // An OrderLineItem with no associated order resolves to null and is skipped.
        $draft = new OrderLineItem();
        $event = new EntityDraftPersistAfterEvent($draft, new OrderLineItem());

        $this->listener->onEntityDraftPersistAfter($event);

        // The listener must not mutate the draft when it cannot resolve an Order.
        self::assertNull($draft->getDraftSource());
    }

    public function testOnEntityDraftPersistAfterRestoresOrderAndLineItemDraftSourcesWhenClearedBefore(): void
    {
        $draftSource = new Order();

        $lineItemDraftSource = new OrderLineItem();
        $draftLineItem = new OrderLineItem();
        $draftLineItem->setDraftSource($lineItemDraftSource);

        $draft = new Order();
        $draft->setDraftSource($draftSource);
        $draft->addLineItem($draftLineItem);

        $beforeEvent = new EntityDraftPersistBeforeEvent($draft, new Order());
        $this->listener->onEntityDraftPersistBefore($beforeEvent);

        $afterEvent = new EntityDraftPersistAfterEvent($draft, new Order());
        $this->listener->onEntityDraftPersistAfter($afterEvent);

        self::assertSame($draftSource, $draft->getDraftSource());
        self::assertSame($lineItemDraftSource, $draftLineItem->getDraftSource());
    }
}
