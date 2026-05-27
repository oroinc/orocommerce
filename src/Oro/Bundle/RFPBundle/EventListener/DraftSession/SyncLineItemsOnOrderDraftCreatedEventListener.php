<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;

/**
 * Creates OrderLineItem drafts for each RequestProduct of the RFQ Request and adds them to the Order draft.
 */
class SyncLineItemsOnOrderDraftCreatedEventListener
{
    public function __construct(
        private readonly EntityDraftFactoryInterface $entityDraftFactory,
    ) {
    }

    public function onEntityDraftCreated(EntityDraftCreatedEvent $event): void
    {
        $request = $event->getEntity();
        $orderDraft = $event->getDraft();

        if (!$request instanceof Request || !$orderDraft instanceof Order) {
            return;
        }

        $draftSessionUuid = $orderDraft->getDraftSessionUuid();

        foreach ($request->getRequestProducts() as $requestProduct) {
            $lineItemDraft = $this->entityDraftFactory->createDraft($requestProduct, $draftSessionUuid);
            assert($lineItemDraft instanceof OrderLineItem);
            $orderDraft->addLineItem($lineItemDraft);
        }
    }
}
