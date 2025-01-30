<?php

namespace Oro\Bundle\OrderBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CustomerBundle\Entity\AbstractAddressToAddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddressToAddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddressToAddressType;
use Oro\Bundle\CustomerBundle\Utils\AddressCopier;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Provider\AddressProviderInterface;

/**
 * Contains get and update methods for order addresses.
 */
class OrderAddressManager extends AbstractAddressManager
{
    private AddressCopier $addressCopier;

    public function __construct(
        ManagerRegistry $doctrine,
        AddressProviderInterface $addressProvider,
        AddressCopier $addressCopier
    ) {
        parent::__construct($doctrine, $addressProvider);

        $this->addressCopier = $addressCopier;
    }

    public function updateFromAbstract(?AbstractAddress $address = null, ?OrderAddress $orderAddress = null): OrderAddress
    {
        if (!$orderAddress) {
            $orderAddress = new OrderAddress();
        }

        if (!$address) {
            $address = new OrderAddress();
        }

        $this->addressCopier->copyToAddress($address, $orderAddress);

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
