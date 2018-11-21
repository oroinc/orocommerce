<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Entity\AddressPhoneAwareInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * Commons implementation of diff mapper for addresses.
 */
abstract class AbstractAddressDiffMapper implements CheckoutStateDiffMapperInterface
{
    /**
     * {@inheritdoc}
     */
    public function getCurrentState($checkout)
    {
        if (!$checkout instanceof Checkout) {
            return null;
        }

        $address = $this->getAddress($checkout);

        if (!$address) {
            return null;
        }

        if ($address->getCustomerAddress()) {
            return $this->getCompareString($address->getCustomerAddress());
        }

        if ($address->getCustomerUserAddress()) {
            return $this->getCompareString($address->getCustomerUserAddress());
        }

        return $this->getCompareString($address);
    }

    /**
     * {@inheritdoc}
     */
    public function isEntitySupported($entity)
    {
        return is_object($entity) && $entity instanceof Checkout;
    }

    /** {@inheritdoc} */
    public function isStatesEqual($entity, $state1, $state2)
    {
        return $state1 === $state2;
    }

    /**
     * @param AbstractAddress $address
     * @return string
     */
    protected function getCompareString(AbstractAddress $address)
    {
        $data = [
            $address->getNamePrefix(),
            $address->getFirstName(),
            $address->getLastName(),
            $address->getMiddleName(),
            $address->getNameSuffix(),
            $address->getOrganization(),
            $address->getStreet(),
            $address->getStreet2(),
            $address->getCity(),
            $address->getUniversalRegion(),
            $address->getCountry(),
            $address->getPostalCode(),
        ];

        if ($address instanceof AddressPhoneAwareInterface) {
            $data[] = $address->getPhone();
        }

        return trim(implode(' ', $data));
    }

    /**
     * @param Checkout $checkout
     * @return OrderAddress
     */
    abstract public function getAddress(Checkout $checkout);
}
