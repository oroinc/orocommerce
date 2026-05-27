<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\DraftSession\Event\EntityDraftPersistAfterEvent;
use Oro\Component\DraftSession\Event\EntityDraftPersistBeforeEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Clears the draft source relations on order draft and its line items before persist and restores after flush.
 */
final class ClearDraftSourceOnOrderDraftPersistEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array<string,Order>
     */
    private array $draftSources = [];

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function onEntityDraftPersistBefore(EntityDraftPersistBeforeEvent $event): void
    {
        $orderDraft = $event->getDraft();
        if ($orderDraft instanceof OrderLineItem) {
            $orderDraft = $orderDraft->getOrder();
        }

        if (!$orderDraft instanceof Order) {
            return;
        }

        $this->draftSources = [];

        $draftSource = $orderDraft->getDraftSource();
        if ($draftSource && $draftSource->getId() === null) {
            // If the entity is new, we need to clear the draft source relation before persist and flush - to avoid
            // error about unintentional non-persisted association - as we should not persist it neither in cascade
            // nor manually.
            $orderDraft->setDraftSource(null);

            $hash = spl_object_hash($orderDraft);
            $this->draftSources[$hash] = $draftSource;
        }

        foreach ($orderDraft->getLineItems() as $lineItem) {
            $lineItemDraftSource = $lineItem->getDraftSource();
            if ($lineItemDraftSource && $lineItemDraftSource->getId() === null) {
                // If the entity is new, we need to clear the draft source relation before persist and flush - to avoid
                // error about unintentional non-persisted association - as we should not persist it neither in cascade
                // nor manually.
                $lineItem->setDraftSource(null);

                $hash = spl_object_hash($lineItem);
                $this->draftSources[$hash] = $lineItemDraftSource;
            }
        }

        $this->logger->debug(
            'Draft source relations were cleared before flush for order draft {draft_id}.',
            [
                'draft_id' => $orderDraft->getId(),
                'draft_session_uuid' => $orderDraft->getDraftSessionUuid(),
            ]
        );
    }

    public function onEntityDraftPersistAfter(EntityDraftPersistAfterEvent $event): void
    {
        $orderDraft = $event->getDraft();
        if ($orderDraft instanceof OrderLineItem) {
            $orderDraft = $orderDraft->getOrder();
        }

        if (!$orderDraft instanceof Order) {
            return;
        }

        $hash = spl_object_hash($orderDraft);
        if (isset($this->draftSources[$hash])) {
            // Restores the draft source relation back after the flush.
            $orderDraft->setDraftSource($this->draftSources[$hash]);
            unset($this->draftSources[$hash]);
        }

        foreach ($orderDraft->getLineItems() as $lineItem) {
            $hash = spl_object_hash($lineItem);
            if (isset($this->draftSources[$hash])) {
                $lineItem->setDraftSource($this->draftSources[$hash]);
                unset($this->draftSources[$hash]);
            }
        }

        $this->logger->debug('Draft source relations were restored after flush for order draft {draft_id}.', [
            'draft_id' => $orderDraft->getId(),
            'draft_session_uuid' => $orderDraft->getDraftSessionUuid(),
        ]);
    }
}
