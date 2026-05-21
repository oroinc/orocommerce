<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession\Factory;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;
use Oro\Component\DraftSession\Provider\EntityDraftRepositoryInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;

/**
 * Creates an order line item draft from an existing order line item by synchronizing their properties.
 */
class OrderLineItemDraftFactory implements EntityDraftFactoryInterface
{
    private ?EntityDraftRepositoryInterface $orderDraftRepository = null;

    public function __construct(
        private readonly EntityDraftSynchronizerInterface $entityDraftSynchronizer,
    ) {
    }

    /**
     * @bc-layer This method exists for BC reasons.
     */
    public function setOrderDraftRepository(?EntityDraftRepositoryInterface $orderDraftRepository): void
    {
        $this->orderDraftRepository = $orderDraftRepository;
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return $entityClass === OrderLineItem::class;
    }

    #[\Override]
    public function createDraft(EntityDraftAwareInterface $entity, string $draftSessionUuid): OrderLineItem
    {
        assert($entity instanceof OrderLineItem);

        $orderLineItemDraft = new OrderLineItem();
        $orderLineItemDraft->setDraftSessionUuid($draftSessionUuid);
        $orderLineItemDraft->setDraftSource($entity);

        $order = $entity->getOrder();
        if ($order !== null) {
            if ($order->getId() !== null) {
                // Existing order may have an existing draft.
                $orderDraft = $this->orderDraftRepository?->findEntityDraft($order, $draftSessionUuid);
            } else {
                // New order may have a reference to its draft.
                $orderDraft = $order->getDrafts()->first() ?: null;
            }

            $orderDraft?->addLineItem($orderLineItemDraft);
        }

        $this->entityDraftSynchronizer->synchronizeToDraft($entity, $orderLineItemDraft);

        return $orderLineItemDraft;
    }
}
