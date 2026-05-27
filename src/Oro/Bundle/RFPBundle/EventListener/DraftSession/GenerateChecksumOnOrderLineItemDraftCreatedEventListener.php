<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;

/**
 * Generates and sets the checksum on every {@see OrderLineItem} in an {@see Order} draft
 * after it is fully built (fields + prices all set) from an RFQ {@see Request}.
 */
class GenerateChecksumOnOrderLineItemDraftCreatedEventListener
{
    public function __construct(
        private readonly LineItemChecksumGeneratorInterface $lineItemChecksumGenerator,
    ) {
    }

    public function onEntityDraftCreated(EntityDraftCreatedEvent $event): void
    {
        if (!$event->getEntity() instanceof Request || !$event->getDraft() instanceof Order) {
            return;
        }

        /** @var Order $orderDraft */
        $orderDraft = $event->getDraft();

        foreach ($orderDraft->getLineItems() as $lineItem) {
            $checksum = $this->lineItemChecksumGenerator->getChecksum($lineItem);
            $lineItem->setChecksum($checksum ?? '');
        }
    }
}
