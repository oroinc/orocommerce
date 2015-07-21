<?php

namespace OroB2B\Bundle\CustomerBundle\Entity\Traits;

use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\CustomerBundle\Entity\AbstractDefaultTypedAddress;

trait AddressEntityTrait
{
    /**
     * Add addresses
     *
     * @param AbstractDefaultTypedAddress $address
     * @return $this
     */
    public function addAddress(AbstractDefaultTypedAddress $address)
    {
        /** @var AbstractDefaultTypedAddress $address */
        if (!$this->getAddresses()->contains($address)) {
            $this->getAddresses()->add($address);
            $address->setOwner($this);
        }

        return $this;
    }

    /**
     * Remove addresses
     *
     * @param AbstractDefaultTypedAddress $addresses
     * @return $this
     */
    public function removeAddress(AbstractDefaultTypedAddress $addresses)
    {
        if ($this->hasAddress($addresses)) {
            $this->getAddresses()->removeElement($addresses);
        }

        return $this;
    }

    /**
     * Get addresses
     *
     * @return Collection
     */
    abstract public function getAddresses();

    /**
     * @param AbstractDefaultTypedAddress $address
     * @return bool
     */
    protected function hasAddress(AbstractDefaultTypedAddress $address)
    {
        return $this->getAddresses()->contains($address);
    }

    /**
     * Gets one address that has specified type name.
     *
     * @param string $typeName
     *
     * @return AbstractDefaultTypedAddress|null
     */
    public function getAddressByTypeName($typeName)
    {
        /** @var AbstractDefaultTypedAddress $address */
        foreach ($this->getAddresses() as $address) {
            if ($address->hasTypeWithName($typeName)) {
                return $address;
            }
        }

        return null;
    }

    /**
     * Gets primary address if it's available.
     *
     * @return AbstractDefaultTypedAddress|null
     */
    public function getPrimaryAddress()
    {
        /** @var AbstractDefaultTypedAddress $address */
        foreach ($this->getAddresses() as $address) {
            if ($address->isPrimary()) {
                return $address;
            }
        }

        return null;
    }
}
