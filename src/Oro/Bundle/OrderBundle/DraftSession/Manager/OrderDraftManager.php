<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\DraftSession\Provider\OrderDraftSessionUuidProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\DraftSession\LoadOrderDraftOnRequestListener;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides methods to manage order drafts in the context of a draft session.
 */
class OrderDraftManager
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly RequestStack $requestStack,
        private readonly OrderDraftSessionUuidProvider $draftSessionUuidProvider,
        private readonly EntityDraftFactoryInterface $entityDraftFactory,
        private readonly EntityDraftSynchronizerInterface $entityDraftSynchronizer,
    ) {
    }

    public function getDraftSessionUuid(): ?string
    {
        return $this->draftSessionUuidProvider->getDraftSessionUuid();
    }

    /**
     * Returns the order draft for the current request if it exists.
     *
     * @return Order|null
     */
    public function getOrderDraft(): ?Order
    {
        $request = $this->requestStack->getMainRequest();

        return $request?->attributes->get(LoadOrderDraftOnRequestListener::ORDER_DRAFT);
    }

    public function createEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): EntityDraftAwareInterface {
        if (!$draftSessionUuid) {
            $draftSessionUuid = $this->getDraftSessionUuid();
        }

        return $this->entityDraftFactory->createDraft($entity, $draftSessionUuid);
    }

    public function synchronizeEntityFromDraft(
        EntityDraftAwareInterface $draft,
        EntityDraftAwareInterface $entity
    ): void {
        $this->entityDraftSynchronizer->synchronizeFromDraft($draft, $entity);
    }

    public function synchronizeEntityToDraft(EntityDraftAwareInterface $entity, EntityDraftAwareInterface $draft): void
    {
        $this->entityDraftSynchronizer->synchronizeToDraft($entity, $draft);
    }

    /**
     * Finds an order draft by a draft session UUID.
     */
    public function findOrderDraft(?string $draftSessionUuid): ?Order
    {
        if (!$draftSessionUuid) {
            $draftSessionUuid = $this->getDraftSessionUuid();
        }

        return $this->doctrine
            ->getRepository(Order::class)
            ->getOrderDraftWithRelations($draftSessionUuid);
    }

    /**
     * Finds an order line item draft by the order line item ID.
     */
    public function findOrderLineItemDraft(
        OrderLineItem $orderLineItem,
        ?string $draftSessionUuid = null
    ): ?OrderLineItem {
        if (!$draftSessionUuid) {
            $draftSessionUuid = $this->getDraftSessionUuid();
        }

        $orderLineItemOrDraftId = $this->getOrderLineItemOrDraftId($orderLineItem);

        return $this->doctrine
            ->getRepository(OrderLineItem::class)
            ->findOrderLineItemDraftWithRelations($orderLineItemOrDraftId, $draftSessionUuid);
    }

    public function createOrderLineItemDraft(
        Order $orderDraft,
        ?OrderLineItem $orderLineItem = null,
        ?string $draftSessionUuid = null
    ): OrderLineItem {
        if (!$draftSessionUuid) {
            $draftSessionUuid = $this->getDraftSessionUuid();
        }

        $orderLineItem ??= new OrderLineItem();

        /** @var OrderLineItem $orderLineItemDraft */
        $orderLineItemDraft = $this->entityDraftFactory->createDraft($orderLineItem, $draftSessionUuid);

        $orderDraft->addLineItem($orderLineItemDraft);

        return $orderLineItemDraft;
    }

    /**
     * Returns the order line item ID if the given order line item is persisted, or its draft ID if it's a new entity.
     */
    public function getOrderLineItemOrDraftId(OrderLineItem $orderLineItem): int
    {
        if ($orderLineItem->getId()) {
            return $orderLineItem->getId();
        }

        // New entity may have a reference to its draft.
        $entityDraft = $orderLineItem->getDrafts()->first() ?: null;
        $entityDraftId = $entityDraft?->getId();

        if (!$entityDraftId) {
            throw new \LogicException('Entity draft is expected to be present for a new order line item.');
        }

        return $entityDraftId;
    }
}
