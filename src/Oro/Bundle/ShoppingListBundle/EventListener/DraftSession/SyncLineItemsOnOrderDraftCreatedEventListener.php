<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;

/**
 * Creates OrderLineItem drafts for each LineItem of the ShoppingList and adds them to the Order draft.
 */
class SyncLineItemsOnOrderDraftCreatedEventListener
{
    public function __construct(
        private readonly EntityDraftFactoryInterface $entityDraftFactory,
    ) {
    }

    public function onEntityDraftCreated(EntityDraftCreatedEvent $event): void
    {
        $shoppingList = $event->getEntity();
        $orderDraft = $event->getDraft();

        if (!$shoppingList instanceof ShoppingList || !$orderDraft instanceof Order) {
            return;
        }

        $draftSessionUuid = $orderDraft->getDraftSessionUuid();

        foreach ($shoppingList->getLineItems() as $lineItem) {
            $lineItemDraft = $this->entityDraftFactory->createDraft($lineItem, $draftSessionUuid);
            assert($lineItemDraft instanceof OrderLineItem);
            $orderDraft->addLineItem($lineItemDraft);
        }
    }
}
