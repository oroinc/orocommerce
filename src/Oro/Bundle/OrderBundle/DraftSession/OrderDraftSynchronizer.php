<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;

/**
 * Synchronizes Order fields and line items between source and target orders during draft sync.
 */
class OrderDraftSynchronizer implements EntityDraftSynchronizerInterface
{
    public function __construct(
        private readonly EntityDraftSyncReferenceResolver $draftSyncReferenceResolver,
        private readonly EntityDraftSynchronizerInterface $entityDraftSynchronizer,
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

        if (!$entity->getId()) {
            $entity->addDraft($draft);
        }

        $this->synchronizeFields($draft, $entity);
        $this->synchronizeLineItemsFromDraft($draft, $entity);
    }

    #[\Override]
    public function synchronizeToDraft(EntityDraftAwareInterface $entity, EntityDraftAwareInterface $draft): void
    {
        assert($entity instanceof Order);
        assert($draft instanceof Order);

        $this->synchronizeFields($entity, $draft);
    }

    private function synchronizeFields(Order $sourceOrder, Order $targetOrder): void
    {
        if ($sourceOrder->getOrganization()) {
            $targetOrder->setOrganization($this->getReference($sourceOrder->getOrganization()));
        }

        $targetOrder->setOwner($this->getReference($sourceOrder->getOwner()));

        $targetOrder->setCustomer($this->getReference($sourceOrder->getCustomer()));
        $targetOrder->setCustomerUser($this->getReference($sourceOrder->getCustomerUser()));
        $targetOrder->setCurrency($sourceOrder->getCurrency());

        if ($sourceOrder->getWebsite()) {
            $targetOrder->setWebsite($this->getReference($sourceOrder->getWebsite()));
        }

        $targetOrder->setShippingStatus(
            $this->draftSyncReferenceResolver->getEnumReference($sourceOrder->getShippingStatus())
        );
        $targetOrder->setShippingMethod($sourceOrder->getShippingMethod());
        $targetOrder->setShippingMethodType($sourceOrder->getShippingMethodType());
        $targetOrder->setEstimatedShippingCostAmount($sourceOrder->getEstimatedShippingCostAmount());
        $targetOrder->setOverriddenShippingCostAmount($sourceOrder->getOverriddenShippingCostAmount());

        $targetOrder->setPoNumber($sourceOrder->getPoNumber());

        if ($sourceOrder->getShipUntil()) {
            $targetOrder->setShipUntil(clone $sourceOrder->getShipUntil());
        } else {
            $targetOrder->setShipUntil(null);
        }

        $targetOrder->setCustomerNotes($sourceOrder->getCustomerNotes());
    }

    private function synchronizeLineItemsFromDraft(Order $orderDraft, Order $order): void
    {
        foreach ($orderDraft->getLineItems() as $lineItemDraft) {
            $lineItem = $lineItemDraft->getDraftSource();
            if ($lineItem === null) {
                // If draft source is null, then the line item is new and should be created
                // based on the line item draft.
                $lineItem = new OrderLineItem();
                $lineItem->addDraft($lineItemDraft);

                $order->addLineItem($lineItem);
            }

            if ($lineItemDraft->isDraftDelete()) {
                $order->removeLineItem($lineItem);
            } else {
                $this->entityDraftSynchronizer->synchronizeFromDraft($lineItemDraft, $lineItem);
            }
        }
    }

    private function getReference(?object $entity): ?object
    {
        return $this->draftSyncReferenceResolver->getReference($entity);
    }
}
