<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;

/**
 * Sets the billing and shipping address on the Order draft created from a Shopping List.
 */
class SetOrderAddressOnOrderDraftCreatedEventListener
{
    public function __construct(
        private readonly EntityDraftSyncReferenceResolver $draftSyncReferenceResolver,
        private readonly OrderAddressManager $orderAddressManager,
    ) {
    }

    public function onEntityDraftCreated(EntityDraftCreatedEvent $event): void
    {
        $entity = $event->getEntity();
        $orderDraft = $event->getDraft();

        if (!$entity instanceof ShoppingList || !$orderDraft instanceof Order) {
            return;
        }

        $billingCollection = $this->orderAddressManager->getGroupedAddresses(
            $orderDraft,
            OrderAddressProvider::ADDRESS_TYPE_BILLING
        );
        $defaultBillingAddress = $billingCollection->getDefaultAddress();
        if ($defaultBillingAddress !== null) {
            $billingAddress = $this->orderAddressManager->updateFromAbstract($defaultBillingAddress);
            $billingAddress->setCustomerAddress($this->getReference($billingAddress->getCustomerAddress()));
            $billingAddress->setCustomerUserAddress($this->getReference($billingAddress->getCustomerUserAddress()));
            $billingAddress->setDraftSessionUuid($orderDraft->getDraftSessionUuid());

            $orderDraft->setBillingAddress($billingAddress);
        }

        $shippingCollection = $this->orderAddressManager->getGroupedAddresses(
            $orderDraft,
            OrderAddressProvider::ADDRESS_TYPE_SHIPPING
        );
        $defaultShippingAddress = $shippingCollection->getDefaultAddress();
        if ($defaultShippingAddress !== null) {
            $shippingAddress = $this->orderAddressManager->updateFromAbstract($defaultShippingAddress);
            $shippingAddress->setCustomerAddress($this->getReference($shippingAddress->getCustomerAddress()));
            $shippingAddress->setCustomerUserAddress($this->getReference($shippingAddress->getCustomerUserAddress()));
            $shippingAddress->setDraftSessionUuid($orderDraft->getDraftSessionUuid());

            $orderDraft->setShippingAddress($shippingAddress);
        }
    }

    private function getReference(?object $entity): ?object
    {
        return $this->draftSyncReferenceResolver->getReference($entity);
    }
}
