<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession\Factory;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;

/**
 * Creates an order line item draft from an existing order line item by synchronizing their properties.
 */
class OrderLineItemDraftFactory implements EntityDraftFactoryInterface
{
    public function __construct(
        private readonly EntityDraftSynchronizerInterface $entityDraftSynchronizer,
    ) {
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
        $orderLineItemDraft->setDraftSource($entity->getId() ? $entity : null);

        $this->entityDraftSynchronizer->synchronizeToDraft($entity, $orderLineItemDraft);

        return $orderLineItemDraft;
    }
}
