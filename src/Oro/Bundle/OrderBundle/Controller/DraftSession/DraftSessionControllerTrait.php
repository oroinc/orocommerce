<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Controller\DraftSession;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * Provides helper methods for controllers that work with draft sessions.
 *
 * Requires {@see OrderDraftManager} to be registered in the controller's service locator
 * via {@see getSubscribedServices()}.
 */
trait DraftSessionControllerTrait
{
    /**
     * Returns the order draft for the current request.
     */
    private function getOrderDraft(): Order
    {
        /** @var OrderDraftManager $orderDraftManager */
        $orderDraftManager = $this->container->get(OrderDraftManager::class);
        $orderDraft = $orderDraftManager->getOrderDraft();

        if ($orderDraft === null) {
            throw $this->createNotFoundException();
        }

        return $orderDraft;
    }

    /**
     * Synchronizes the given order with the order draft.
     * If the given order is null, a new order will be created and synchronized with the draft.
     */
    private function syncFromOrderDraft(Order $orderDraft, ?Order $order): Order
    {
        $order ??= new Order();

        /** @var OrderDraftManager $orderDraftManager */
        $orderDraftManager = $this->container->get(OrderDraftManager::class);
        $orderDraftManager->synchronizeEntityFromDraft($orderDraft, $order);

        return $order;
    }

    private function findOrCreateOrderLineItemDraft(Order $orderDraft, OrderLineItem $orderLineItem): OrderLineItem
    {
        /** @var OrderDraftManager $orderDraftManager */
        $orderDraftManager = $this->container->get(OrderDraftManager::class);
        $orderLineItemDraft = $orderDraftManager->findOrderLineItemDraft($orderLineItem);

        if ($orderLineItemDraft === null) {
            $orderLineItemDraft = $orderDraftManager->createOrderLineItemDraft($orderDraft, $orderLineItem);
        }

        return $orderLineItemDraft;
    }

    /**
     * Returns the order line item ID if the given order line item is persisted, or its draft ID if it's a new entity.
     */
    private function getOrderLineItemOrDraftId(OrderLineItem $orderLineItem): int
    {
        /** @var OrderDraftManager $orderDraftManager */
        $orderDraftManager = $this->container->get(OrderDraftManager::class);

        return $orderDraftManager->getOrderLineItemOrDraftId($orderLineItem);
    }

    /**
     * Returns the source order line item for the given order line item draft.
     * If the given order line item is not a draft, returns it as is.
     */
    private function getOrderLineItemDraftSource(OrderLineItem $orderLineItem): OrderLineItem
    {
        if (!$orderLineItem->getDraftSessionUuid()) {
            // Not an order line item draft, return as is.
            return $orderLineItem;
        }

        if ($orderLineItem->getDraftSource() === null) {
            throw $this->createNotFoundException('A draft source for the order line item draft is not found.');
        }

        return $orderLineItem->getDraftSource();
    }
}
