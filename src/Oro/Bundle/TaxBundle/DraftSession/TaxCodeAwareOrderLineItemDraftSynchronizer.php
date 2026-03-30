<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\DraftSession;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;

/**
 * Synchronizes the freeFormTaxCode field between source and target order line item during draft sync.
 */
class TaxCodeAwareOrderLineItemDraftSynchronizer implements EntityDraftSynchronizerInterface
{
    public function __construct(
        private readonly EntityDraftSyncReferenceResolver $draftSyncReferenceResolver,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return $entityClass === OrderLineItem::class;
    }

    #[\Override]
    public function synchronizeFromDraft(
        EntityDraftAwareInterface $draft,
        EntityDraftAwareInterface $entity,
    ): void {
        assert($draft instanceof OrderLineItem);
        assert($entity instanceof OrderLineItem);

        $entity->setFreeFormTaxCode($this->draftSyncReferenceResolver->getReference($draft->getFreeFormTaxCode()));
    }

    #[\Override]
    public function synchronizeToDraft(
        EntityDraftAwareInterface $entity,
        EntityDraftAwareInterface $draft,
    ): void {
        assert($entity instanceof OrderLineItem);
        assert($draft instanceof OrderLineItem);

        $draft->setFreeFormTaxCode($this->draftSyncReferenceResolver->getReference($entity->getFreeFormTaxCode()));
    }
}
