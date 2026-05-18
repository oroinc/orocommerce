<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\DraftSession;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;

/**
 * Synchronizes the requestProduct extended field between the source and draft OrderLineItem
 * so that the RFQ offers form extension can resolve the originating RequestProduct.
 */
class RequestProductAwareOrderLineItemDraftSynchronizer implements EntityDraftSynchronizerInterface
{
    public function __construct(
        private readonly EntityDraftSyncReferenceResolver $draftSyncReferenceResolver,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return is_a($entityClass, OrderLineItem::class, true);
    }

    #[\Override]
    public function synchronizeFromDraft(EntityDraftAwareInterface $draft, EntityDraftAwareInterface $entity): void
    {
        assert($draft instanceof OrderLineItem);
        assert($entity instanceof OrderLineItem);

        $entity->setRequestProduct($this->draftSyncReferenceResolver->getReference($draft->getRequestProduct()));
    }

    #[\Override]
    public function synchronizeToDraft(EntityDraftAwareInterface $entity, EntityDraftAwareInterface $draft): void
    {
        assert($draft instanceof OrderLineItem);
        assert($entity instanceof OrderLineItem);

        $draft->setRequestProduct($this->draftSyncReferenceResolver->getReference($entity->getRequestProduct()));
    }
}
