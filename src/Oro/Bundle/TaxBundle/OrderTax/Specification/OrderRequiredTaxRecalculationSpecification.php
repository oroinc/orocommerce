<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Specification;

use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * This specification specifies an Order which taxes was not calculated
 * or Order which was changed in a way that could lead to the different result of the tax calculation
 */
class OrderRequiredTaxRecalculationSpecification implements SpecificationInterface
{
    use OriginalDataAccessorTrait;

    /**
     * @var OrderWithChangedLineItemsCollectionSpecification
     */
    protected $orderLineItemsCollectionChangedSpecification;

    public function __construct(UnitOfWork $unitOfWork)
    {
        $this->unitOfWork = $unitOfWork;
        $this->orderLineItemsCollectionChangedSpecification = new OrderWithChangedLineItemsCollectionSpecification(
            $unitOfWork
        );
    }

    /**
     * @param Order|OrderLineItem $entity
     *
     * @return bool
     */
    public function isSatisfiedBy($entity): bool
    {
        if (!$entity instanceof Order) {
            return false;
        }

        if (!$entity->getId()) {
            return true;
        }

        return $this->isOrderChanged($entity);
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    private function isOrderChanged(Order $order): bool
    {
        $originalOrderData = $this->getOriginalEntityData($order);
        /**
         * If entity was not loaded it means no changes was made to the entity because it
         * is either proxy or reference
         */
        if (!$originalOrderData) {
            return false;
        }

        if ($this->orderLineItemsCollectionChangedSpecification->isSatisfiedBy($order)) {
            return true;
        }

        $newCustomerId = $order->getCustomer() ? $order->getCustomer()->getId() : null;
        $oldCustomerId = !empty($originalOrderData['customer']) ? $originalOrderData['customer']->getId() : null;
        if ($newCustomerId != $oldCustomerId) {
            return true;
        }

        if ($this->isOrderAddressChanged($order->getShippingAddress(), $originalOrderData['shippingAddress'])
            || $this->isOrderAddressChanged($order->getBillingAddress(), $originalOrderData['billingAddress'])
        ) {
            return true;
        }

        return $order->getOverriddenShippingCostAmount() !== $originalOrderData['overriddenShippingCostAmount']
            || ($order->getEstimatedShippingCostAmount() !== $originalOrderData['estimatedShippingCostAmount']);
    }

    /**
     * @param OrderAddress|null $newAddress
     * @param OrderAddress|null $oldAddress
     *
     * @return bool
     */
    protected function isOrderAddressChanged(?OrderAddress $newAddress, ?OrderAddress $oldAddress)
    {
        if (null === $newAddress && null === $oldAddress) {
            return false;
        }

        if (null === $newAddress || null === $oldAddress) {
            return true;
        }

        if ($this->isCustomerUserAddressChanged($newAddress, $oldAddress)) {
            return true;
        }

        if ($this->isCustomerAddressChanged($newAddress, $oldAddress)) {
            return true;
        }

        return $this->isAddressChanged($newAddress, $oldAddress);
    }

    private function isCustomerUserAddressChanged(OrderAddress $newAddress, OrderAddress $oldAddress): bool
    {
        $newCustomerUserAddressId = $newAddress->getCustomerUserAddress()
            ? $newAddress->getCustomerUserAddress()->getId()
            : null;
        $oldCustomerUserAddressId = $oldAddress->getCustomerUserAddress()
            ? $oldAddress->getCustomerUserAddress()->getId()
            : null;

        return $newCustomerUserAddressId !== $oldCustomerUserAddressId;
    }

    private function isCustomerAddressChanged(OrderAddress $newAddress, OrderAddress $oldAddress)
    {
        $newCustomerAddressId = $newAddress->getCustomerAddress()
            ? $newAddress->getCustomerAddress()->getId()
            : null;
        $oldCustomerAddressId = $oldAddress->getCustomerAddress()
            ? $oldAddress->getCustomerAddress()->getId()
            : null;

        return $newCustomerAddressId !== $oldCustomerAddressId;
    }

    /**
     * @param AbstractAddress|null $newAddress
     * @param AbstractAddress|null $oldAddress
     *
     * @return bool
     */
    private function isAddressChanged(?AbstractAddress $newAddress, ?AbstractAddress $oldAddress)
    {
        if (null === $newAddress && null === $oldAddress) {
            return false;
        }

        if (null === $newAddress || null === $oldAddress) {
            return true;
        }

        $originalAddressData = $this->getOriginalEntityData($newAddress);

        /**
         * If entity was not loaded it means no changes was made to the entity because it
         * is either proxy or reference
         */
        if (!$originalAddressData) {
            return false;
        }

        $originalPostalCode = $originalAddressData['postalCode'] ?? null;
        if ($newAddress->getPostalCode() != $originalPostalCode) {
            return true;
        }

        $oldAddressRegionName = $this->getOriginalRegionName($originalAddressData);
        if ($newAddress->getRegionName() != $oldAddressRegionName) {
            return true;
        }

        $oldAddressCountryIso2 = $originalAddressData['country'] instanceof Country
            ? $originalAddressData['country']->getIso2Code()
            : null;

        return $newAddress->getCountryIso2() != $oldAddressCountryIso2;
    }

    private function getOriginalRegionName(array $originalAddressData): ?string
    {
        $originalRegionName = null;
        if (!empty($originalAddressData['region']) && $originalAddressData['region'] instanceof Region) {
            $originalRegionName = $originalAddressData['region']->getName();
        } elseif (!empty($originalAddressData['regionText'])) {
            $originalRegionName = (string)$originalAddressData['regionText'];
        }

        return $originalRegionName;
    }
}
