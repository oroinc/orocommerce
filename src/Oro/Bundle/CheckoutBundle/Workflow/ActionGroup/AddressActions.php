<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Utils\AddressCopier;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Duplicator\DuplicatorFactory;

/**
 * Checkout workflow Address-related actions.
 */
class AddressActions implements AddressActionsInterface
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
        private DuplicatorFactory $duplicatorFactory,
        private ActionExecutor $actionExecutor,
        private AddressCopier $addressCopier
    ) {
    }

    public function setAddressDuplicatorConfig(array $addressDuplicatorConfig): void
    {
        $this->addressDuplicatorConfig = $addressDuplicatorConfig;
    }

    #[\Override]
    public function updateBillingAddress(
        Checkout $checkout,
        bool $disallowShippingAddressEdit = false
    ): bool {
        $sourceEntity = $checkout->getSourceEntity();
        if ($sourceEntity instanceof ShoppingList && !$sourceEntity->getCustomerUser()) {
            $sourceEntity->setCustomer($checkout->getCustomer());
            $sourceEntity->setCustomerUser($checkout->getCustomerUser());
            foreach ($sourceEntity->getTotals() as $shoppingListTotal) {
                $shoppingListTotal->setCustomerUser($checkout->getCustomerUser());
            }
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

        return $billingAddressHasShipping;
    }

    #[\Override]
    public function updateShippingAddress(Checkout $checkout): void
    {
        if (!$checkout->isShipToBillingAddress()) {
            return;
        }

        $em = $this->registry->getManagerForClass(OrderAddress::class);

        if ($checkout->getShippingAddress()) {
            $em->remove($checkout->getShippingAddress());
        }

        $newShippingAddress = $this->duplicateOrderAddress($checkout->getBillingAddress());

        $checkout->setShippingAddress($newShippingAddress);

        $em->persist($newShippingAddress);
        $em->flush();
    }

    #[\Override]
    public function duplicateOrderAddress(OrderAddress $address): OrderAddress
    {
        return $this->duplicatorFactory->create()->duplicate($address, $this->addressDuplicatorConfig);
    }

    #[\Override]
    public function actualizeAddresses(Checkout $checkout, Order $order): void
    {
        $em = $this->registry->getManagerForClass(CustomerUserAddress::class);

        $customerUserBillingAddress = null;
        if ($checkout->isSaveBillingAddress()) {
            $customerUserBillingAddress = $this->actualizeAddress(
                $order->getBillingAddress(),
                $checkout,
                AddressType::TYPE_BILLING,
                'oro_order_address_billing_allow_manual'
            );
        }

        $customerUserShippingAddress = null;
        if ($checkout->isSaveShippingAddress()) {
            if (
                $customerUserBillingAddress
                && $checkout->isShipToBillingAddress()
                && $checkout->isSaveBillingAddress()
            ) {
                /** @var AddressType $shippingType */
                $shippingType = $em->getReference(AddressType::class, AddressType::TYPE_SHIPPING);
                $customerUserBillingAddress->addType($shippingType);
            } else {
                $customerUserShippingAddress = $this->actualizeAddress(
                    $order->getShippingAddress(),
                    $checkout,
                    AddressType::TYPE_SHIPPING,
                    'oro_order_address_shipping_allow_manual'
                );
            }
        }

        $needFlush = false;
        if ($customerUserBillingAddress) {
            $checkout->getBillingAddress()->setCustomerUserAddress($customerUserBillingAddress);
            $needFlush = true;
        }

        if ($customerUserShippingAddress) {
            $checkout->getShippingAddress()->setCustomerUserAddress($customerUserShippingAddress);
            $needFlush = true;
        }

        if ($needFlush) {
            $em->flush();
        }
    }

    private function actualizeAddress(
        OrderAddress $orderAddress,
        Checkout $checkout,
        string $addressTypeName,
        string $aclResource
    ): ?CustomerUserAddress {
        if (
            $orderAddress->getCustomerAddress()
            || $orderAddress->getCustomerUserAddress()
            || !$this->isGranted($aclResource)
        ) {
            return null;
        }

        $em = $this->registry->getManagerForClass(CustomerUserAddress::class);
        /** @var AddressType $addressType */
        $addressType = $em->getReference(AddressType::class, $addressTypeName);
        $customerUserAddress = new CustomerUserAddress();
        $this->addressCopier->copyToAddress($orderAddress, $customerUserAddress);
        $customerUserAddress->setFrontendOwner($checkout->getCustomerUser())
            ->setOwner($checkout->getOwner())
            ->setSystemOrganization($checkout->getOrganization())
            ->addType($addressType);

        $em->persist($customerUserAddress);

        $orderAddress->setCustomerUserAddress($customerUserAddress);

        return $customerUserAddress;
    }

    private function isGranted(string $attribute): bool
    {
        return $this->actionExecutor->evaluateExpression('acl_granted', [$attribute]);
    }
}
