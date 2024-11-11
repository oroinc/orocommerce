<?php

namespace Oro\Bundle\OrderBundle\Manager;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CustomerBundle\Entity\AbstractAddressToAddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddressToAddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddressToAddressType;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * Contains get and update methods for order addresses.
 */
class OrderAddressManager extends AbstractAddressManager
{
    public function updateFromAbstract(
        AbstractAddress $address = null,
        OrderAddress $orderAddress = null
    ): OrderAddress {
        if (!$orderAddress) {
            $orderAddress = new OrderAddress();
        }

        if (null !== $address) {
            $this->copyAddress($address, $orderAddress);
        }
        $orderAddress->setCustomerAddress(null);
        $orderAddress->setCustomerUserAddress(null);
        if ($address instanceof CustomerAddress) {
            $orderAddress->setCustomerAddress($address);
        } elseif ($address instanceof CustomerUserAddress) {
            $orderAddress->setCustomerUserAddress($address);
        }

        return $orderAddress;
    }

    /**
     * @param OrderAddress[] $addresses
     * @param string         $groupLabelPrefix
     *
     * @return array [address identifier => [address type, ...], ...]
     */
    public function getAddressTypes(array $addresses = [], string $groupLabelPrefix = 'oro.order.'): array
    {
        return array_merge(
            $this->getTypesMapping(
                CustomerAddressToAddressType::class,
                $groupLabelPrefix . static::CUSTOMER_LABEL,
                $addresses
            ),
            $this->getTypesMapping(
                CustomerUserAddressToAddressType::class,
                $groupLabelPrefix . static::CUSTOMER_USER_LABEL,
                $addresses
            )
        );
    }

    protected function getTypesMapping(string $typeEntity, string $typeKey, array $addresses = []): array
    {
        $mapping = [];
        $addresses = \array_key_exists($typeKey, $addresses)
            ? array_values($addresses[$typeKey])
            : [];
        if ($addresses) {
            /** @var AbstractAddressToAddressType[] $types */
            $types = $this->doctrine->getRepository($typeEntity)->findBy(['address' => $addresses]);
            foreach ($types as $type) {
                $mapping[$this->getIdentifier($type->getAddress())][] = $type->getType()->getName();
            }
        }

        return $mapping;
    }
}
