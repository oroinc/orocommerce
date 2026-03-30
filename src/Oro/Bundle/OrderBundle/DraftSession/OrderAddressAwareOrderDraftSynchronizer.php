<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;

/**
 * Synchronizes the billing and shipping address fields between source and target order.
 */
class OrderAddressAwareOrderDraftSynchronizer implements EntityDraftSynchronizerInterface
{
    public function __construct(
        private readonly EntityDraftSyncReferenceResolver $draftSyncReferenceResolver,
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

        $this->synchronizeAddresses($draft, $entity);
    }

    #[\Override]
    public function synchronizeToDraft(EntityDraftAwareInterface $entity, EntityDraftAwareInterface $draft): void
    {
        assert($entity instanceof Order);
        assert($draft instanceof Order);

        $this->synchronizeAddresses($entity, $draft);
    }

    private function synchronizeAddresses(Order $sourceOrder, Order $targetOrder): void
    {
        if ($sourceOrder->getBillingAddress()) {
            $this->syncOrderAddressProperties(
                $sourceOrder->getBillingAddress(),
                $targetOrder->getBillingAddress(),
                $targetOrder,
                AddressType::TYPE_BILLING
            );
        }

        if ($sourceOrder->getShippingAddress()) {
            $this->syncOrderAddressProperties(
                $sourceOrder->getShippingAddress(),
                $targetOrder->getShippingAddress(),
                $targetOrder,
                AddressType::TYPE_SHIPPING
            );
        }
    }

    private function syncOrderAddressProperties(
        OrderAddress $sourceAddress,
        ?OrderAddress $targetAddress,
        Order $targetOrder,
        string $addressType
    ): void {
        if (!$targetAddress) {
            $targetAddress = new OrderAddress();
            if ($addressType === AddressType::TYPE_BILLING) {
                $targetOrder->setBillingAddress($targetAddress);
            } else {
                $targetOrder->setShippingAddress($targetAddress);
            }
        }

        $targetAddress->setLabel($sourceAddress->getLabel());
        $targetAddress->setOrganization($sourceAddress->getOrganization());
        $targetAddress->setNamePrefix($sourceAddress->getNamePrefix());
        $targetAddress->setFirstName($sourceAddress->getFirstName());
        $targetAddress->setMiddleName($sourceAddress->getMiddleName());
        $targetAddress->setLastName($sourceAddress->getLastName());
        $targetAddress->setNameSuffix($sourceAddress->getNameSuffix());
        $targetAddress->setStreet($sourceAddress->getStreet());
        $targetAddress->setStreet2($sourceAddress->getStreet2());
        $targetAddress->setCity($sourceAddress->getCity());
        $targetAddress->setRegion($this->getReference($sourceAddress->getRegion()));
        $targetAddress->setRegionText($sourceAddress->getRegionText());
        $targetAddress->setPostalCode($sourceAddress->getPostalCode());
        $targetAddress->setCountry($this->getReference($sourceAddress->getCountry()));
        $targetAddress->setPhone($sourceAddress->getPhone());
        $targetAddress->setCustomerAddress($this->getReference($sourceAddress->getCustomerAddress()));
        $targetAddress->setCustomerUserAddress($this->getReference($sourceAddress->getCustomerUserAddress()));
        $targetAddress->setFromExternalSource($sourceAddress->isFromExternalSource());

        if ($sourceAddress->getValidatedAt()) {
            $targetAddress->setValidatedAt(clone $sourceAddress->getValidatedAt());
        } else {
            $targetAddress->setValidatedAt(null);
        }
    }

    private function getReference(?object $entity): ?object
    {
        return $this->draftSyncReferenceResolver->getReference($entity);
    }
}
