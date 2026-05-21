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
use Oro\Component\DraftSession\Manager\EntityDraftManager;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;
use Oro\Component\DraftSession\Util\EntityDraftUtils;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides methods to manage order drafts in the context of a draft session.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderDraftManager
{
    private ?EntityDraftManager $entityDraftManager = null;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly RequestStack $requestStack,
        private readonly OrderDraftSessionUuidProvider $draftSessionUuidProvider,
        private readonly EntityDraftFactoryInterface $entityDraftFactory,
        private readonly EntityDraftSynchronizerInterface $entityDraftSynchronizer,
    ) {
    }

    /**
     * @bc-layer This method exists for BC reasons.
     */
    public function setEntityDraftManager(?EntityDraftManager $entityDraftManager): void
    {
        $this->entityDraftManager = $entityDraftManager;
    }

    public function getDraftSessionUuid(): ?string
    {
        return $this->draftSessionUuidProvider->getDraftSessionUuid();
    }

    /**
     * Determines whether a draft exists for the given entity in the resolved draft session.
     *
     * @param EntityDraftAwareInterface $entity Entity to check draft presence for.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return bool True when a matching draft exists; otherwise false.
     */
    public function hasEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): bool {
        return $this->entityDraftManager->hasEntityDraft($entity, $draftSessionUuid);
    }

    /**
     * Finds a draft for the given entity in the resolved draft session.
     *
     * @param EntityDraftAwareInterface $entity Entity to find a draft for.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return EntityDraftAwareInterface|null Draft entity when found; otherwise null.
     */
    public function findEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): ?EntityDraftAwareInterface {
        return $this->entityDraftManager->findEntityDraft($entity, $draftSessionUuid);
    }

    /**
     * Loads entity state from its draft using loader service logic.
     *
     * @param EntityDraftAwareInterface $entity Regular entity or draft entity.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return EntityDraftAwareInterface Synchronized regular entity instance.
     */
    public function loadFromEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): EntityDraftAwareInterface {
        return $this->entityDraftManager->loadFromEntityDraft($entity, $draftSessionUuid);
    }

    /**
     * Saves draft state for the given entity using persister service logic.
     *
     * @param EntityDraftAwareInterface $entity Regular entity or draft entity.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return EntityDraftAwareInterface Persisted draft entity.
     */
    public function saveToEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): EntityDraftAwareInterface {
        return $this->entityDraftManager->saveToEntityDraft($entity, $draftSessionUuid);
    }

    /**
     * Deletes a draft for the given entity in the resolved draft session.
     *
     * @param EntityDraftAwareInterface $entity Entity whose draft should be removed.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     */
    public function deleteEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): void {
        $this->entityDraftManager->deleteEntityDraft($entity, $draftSessionUuid);
    }

    /**
     * @bc-layer This method is retained for BC reasons. Use ::findEntityDraft() instead.
     */
    public function getOrderDraft(): ?Order
    {
        $request = $this->requestStack->getMainRequest();

        return $request?->attributes->get(LoadOrderDraftOnRequestListener::ORDER_DRAFT);
    }

    /**
     * @bc-layer This method is retained for BC reasons. Use ::saveToEntityDraft() instead.
     */
    public function createEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): EntityDraftAwareInterface {
        if (!$draftSessionUuid) {
            $draftSessionUuid = $this->getDraftSessionUuid();
        }

        return $this->entityDraftFactory->createDraft($entity, $draftSessionUuid);
    }

    /**
     * @bc-layer This method is retained for BC reasons. Use ::loadFromEntityDraft() instead.
     */
    public function synchronizeEntityFromDraft(
        EntityDraftAwareInterface $draft,
        EntityDraftAwareInterface $entity
    ): void {
        $this->entityDraftSynchronizer->synchronizeFromDraft($draft, $entity);
    }

    /**
     * @bc-layer This method is retained for BC reasons. Use ::saveToEntityDraft() instead.
     */
    public function synchronizeEntityToDraft(EntityDraftAwareInterface $entity, EntityDraftAwareInterface $draft): void
    {
        $this->entityDraftSynchronizer->synchronizeToDraft($entity, $draft);
    }

    /**
     * @bc-layer This method is retained for BC reasons. Use ::findEntityDraft() instead.
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
     * @bc-layer This method is retained for BC reasons. Use ::findEntityDraft() instead.
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

    /**
     * @bc-layer This method is retained for BC reasons. Use ::saveToEntityDraft() instead.
     */
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
     * @bc-layer This method is retained for BC reasons. Use {@see EntityDraftUtils::getEntityOrDraftId()} instead.
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
