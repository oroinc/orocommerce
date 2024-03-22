<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Duplicator\DuplicatorFactory;

/**
 * Checkout workflow Address-related actions.
 */
class AddressActions
{
    private array $addressDuplicatorConfig = [
        [['setNull'], ['propertyName', ['id']]],
        [['keep'], ['propertyName', ['customerAddress']]],
        [['keep'], ['propertyName', ['customerUserAddress']]],
        [['keep'], ['propertyName', ['city']]],
        [['keep'], ['propertyName', ['country']]],
        [['keep'], ['propertyName', ['region']]],
        [['keep'], ['propertyName', ['organization']]],
        [['shallowCopy'], ['propertyType', [\DateTime::class]]]
    ];

    public function __construct(
        private ManagerRegistry $registry,
        private DuplicatorFactory $duplicatorFactory
    ) {
    }

    public function setAddressDuplicatorConfig(array $addressDuplicatorConfig): void
    {
        $this->addressDuplicatorConfig = $addressDuplicatorConfig;
    }

    public function updateBillingAddress(
        Checkout $checkout,
        bool $disallowShippingAddressEdit = false
    ): array {
        $sourceEntity = $checkout->getSourceEntity();
        if ($sourceEntity instanceof ShoppingList && !$sourceEntity->getCustomerUser()) {
            $sourceEntity->setCustomer($checkout->getCustomer());
            $sourceEntity->setCustomerUser($checkout->getCustomerUser());
        }

        $billingAddressHasShipping = true;
        if ($checkout->getBillingAddress()->getCustomerAddress()) {
            $billingAddressHasShipping = $checkout->getBillingAddress()
                ->getCustomerAddress()
                ->hasTypeWithName(AddressType::TYPE_SHIPPING);
        }

        if ($checkout->getBillingAddress()->getCustomerUserAddress()) {
            $billingAddressHasShipping = $checkout->getBillingAddress()
                ->getCustomerUserAddress()
                ->hasTypeWithName(AddressType::TYPE_SHIPPING);
        }

        if (!$disallowShippingAddressEdit && $billingAddressHasShipping) {
            $this->updateShippingAddress($checkout);
        }

        return ['billing_address_has_shipping' => $billingAddressHasShipping];
    }

    public function updateShippingAddress(Checkout $checkout): void
    {
        if (!$checkout->isShipToBillingAddress()) {
            return;
        }

        $em = $this->registry->getManagerForClass(OrderAddress::class);

        if ($checkout->getShippingAddress()) {
            $em->remove($checkout->getShippingAddress());
        }

        $newShippingAddress = $this->duplicateOrderAddress($checkout->getBillingAddress())['newAddress'];

        $checkout->setShippingAddress($newShippingAddress);
        if ($newShippingAddress) {
            $em->persist($newShippingAddress);
            $em->flush();
        }
    }

    public function duplicateOrderAddress(OrderAddress $address): array
    {
        $duplicator = $this->duplicatorFactory->create();

        return ['newAddress' => $duplicator->duplicate($address, $this->addressDuplicatorConfig)];
    }
}
