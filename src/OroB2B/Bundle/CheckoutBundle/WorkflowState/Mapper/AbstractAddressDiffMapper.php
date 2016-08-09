<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\AccountBundle\Entity\AddressPhoneAwareInterface;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

abstract class AbstractAddressDiffMapper implements CheckoutStateDiffMapperInterface
{
    use IsStateEqualTrait;

    /**
     * @param Checkout $checkout
     * @return OrderAddress
     */
    abstract public function getAddress(Checkout $checkout);

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
     * @return array
     */
    public function getCurrentState($checkout)
    {
        $address = $this->getAddress($checkout);

        if (!$address) {
            return [];
        }

        if ($address->getAccountAddress()) {
            return $this->getCompareString($address->getAccountAddress());
        }

        if ($address->getAccountUserAddress()) {
            return $this->getCompareString($address->getAccountUserAddress());
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
}
