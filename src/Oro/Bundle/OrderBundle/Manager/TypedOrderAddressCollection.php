<?php

namespace Oro\Bundle\OrderBundle\Manager;

use Oro\Bundle\CustomerBundle\Entity\AbstractDefaultTypedAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;

/**
 * Provides default address of addresses array
 */
class TypedOrderAddressCollection
{
    /** @var CustomerUser */
    protected $customerUser;

    /** @var string */
    protected $type;

    /** @var array */
    protected $addresses = [];

    /** @var int */
    protected $defaultAddressKey;

    /** @var AbstractDefaultTypedAddress */
    protected $defaultAddress;

    /**
     * @param CustomerUser $customerUser
     * @param string $type
     * @param array $addresses
     */
    public function __construct(CustomerUser $customerUser = null, $type, array $addresses = [])
    {
        $this->customerUser = $customerUser;
        $this->type = $type;
        $this->addresses = $addresses;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->addresses;
    }

    /**
     * @return null|int
     */
    public function getDefaultAddressKey()
    {
        $this->ensureDefaultAddress();

        return $this->defaultAddressKey;
    }

    /**
     * @return null|AbstractDefaultTypedAddress
     */
    public function getDefaultAddress()
    {
        $this->ensureDefaultAddress();

        return $this->defaultAddress;
    }

    protected function ensureDefaultAddress()
    {
        if (!$this->addresses || $this->defaultAddress || null === $this->customerUser) {
            return;
        }

        $addresses = array_merge(...array_values($this->addresses));

        /** @var AbstractDefaultTypedAddress[] $addresses */
        foreach ($addresses as $key => $address) {
            if ($address->hasDefault($this->type)) {
                $this->defaultAddressKey = $key;
                $this->defaultAddress = $address;

                if ($this->isAddressCustomerUserApplicable($address)) {
                    break;
                }
            }
        }

        if (!isset($this->defaultAddress)) {
            $this->ensureFirstApplicableAddressAsDefault($addresses);
        }
    }

    /**
     * @param AbstractDefaultTypedAddress[] $addresses
     */
    protected function ensureFirstApplicableAddressAsDefault(array $addresses)
    {
        $this->defaultAddressKey = array_key_first($addresses);
        $this->defaultAddress = $addresses[$this->defaultAddressKey];
    }

    protected function isAddressCustomerUserApplicable(AbstractDefaultTypedAddress $address): bool
    {
        $frontendOwner = $address->getFrontendOwner();

        return $address instanceof CustomerUserAddress &&
            $frontendOwner &&
            $frontendOwner->getId() === $this->customerUser->getId();
    }
}
