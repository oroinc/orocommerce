<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Synchronizes the payment term field between source and target order.
 */
class PaymentTermAwareOrderDraftSynchronizer implements EntityDraftSynchronizerInterface
{
    public function __construct(
        private readonly EntityDraftSyncReferenceResolver $draftSyncReferenceResolver,
        private readonly PaymentTermAssociationProvider $paymentTermAssociationProvider,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return $entityClass === Order::class;
    }

    #[\Override]
    public function synchronizeFromDraft(EntityDraftAwareInterface $draft, EntityDraftAwareInterface $entity): void
    {
        assert($draft instanceof Order);
        assert($entity instanceof Order);

        $this->synchronizePaymentTerms($draft, $entity);
    }

    #[\Override]
    public function synchronizeToDraft(EntityDraftAwareInterface $entity, EntityDraftAwareInterface $draft): void
    {
        assert($entity instanceof Order);
        assert($draft instanceof Order);

        $this->synchronizePaymentTerms($entity, $draft);
    }

    private function synchronizePaymentTerms(Order $sourceOrder, Order $targetOrder): void
    {
        foreach ($this->paymentTermAssociationProvider->getAssociationNames(Order::class) as $associationName) {
            $paymentTerm = $this->paymentTermAssociationProvider->getPaymentTerm($sourceOrder, $associationName);
            if ($this->propertyAccessor->isWritable($targetOrder, $associationName)) {
                $this->propertyAccessor->setValue(
                    $targetOrder,
                    $associationName,
                    $this->draftSyncReferenceResolver->getReference($paymentTerm),
                );
            }
        }
    }
}
