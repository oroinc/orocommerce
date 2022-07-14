<?php

namespace Oro\Bundle\OrderBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CustomerBundle\Entity\AbstractAddressToAddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Provider\AddressProviderInterface;

/**
 * Class OrderAddressManager - service that contains get and update methods for order addresses
 */
class OrderAddressManager extends AbstractAddressManager
{
    /** @var string */
    protected $orderAddressClass;

    /**
     * @param AddressProviderInterface $addressProvider
     * @param ManagerRegistry $registry
     * @param string $orderAddressClass
     */
    public function __construct(
        AddressProviderInterface $addressProvider,
        ManagerRegistry $registry,
        $orderAddressClass
    ) {
        $this->orderAddressClass = $orderAddressClass;

        parent::__construct($addressProvider, $registry);
    }

    /**
     * @param AbstractAddress $address
     * @param OrderAddress $orderAddress
     * @return OrderAddress
     */
    public function updateFromAbstract(AbstractAddress $address = null, OrderAddress $orderAddress = null)
    {
        if (!$orderAddress) {
            $orderAddress = new $this->orderAddressClass();
        }

        if ($address) {
            $addressClassName = ClassUtils::getClass($address);
            $addressMetadata = $this->registry->getManagerForClass($addressClassName)
                ->getClassMetadata($addressClassName);

            foreach ($addressMetadata->getFieldNames() as $fieldName) {
                $this->setValue($address, $orderAddress, $fieldName);
            }

            foreach ($addressMetadata->getAssociationNames() as $associationName) {
                $this->setValue($address, $orderAddress, $associationName);
            }
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
     * @param array|OrderAddress[] $addresses
     * @param string $groupLabelPrefix
     * @return array
     */
    public function getAddressTypes(array $addresses = [], $groupLabelPrefix = 'oro.order.')
    {
        return array_merge(
            $this->getTypesMapping(
                'OroCustomerBundle:CustomerAddressToAddressType',
                $groupLabelPrefix . static::ACCOUNT_LABEL,
                $addresses
            ),
            $this->getTypesMapping(
                'OroCustomerBundle:CustomerUserAddressToAddressType',
                $groupLabelPrefix . static::ACCOUNT_USER_LABEL,
                $addresses
            )
        );
    }

    /**
     * @param string $typeEntity
     * @param string $typeKey
     * @param array $addresses
     * @return array
     */
    protected function getTypesMapping($typeEntity, $typeKey, array $addresses = [])
    {
        $addresses = array_key_exists($typeKey, $addresses) ? array_values($addresses[$typeKey]) : [];

        $mapping = [];
        if ($addresses) {
            /** @var AbstractAddressToAddressType[] $types */
            $types = $this->registry
                ->getManagerForClass($typeEntity)
                ->getRepository($typeEntity)
                ->findBy(['address' => $addresses]);

            foreach ($types as $typeData) {
                $mapping[$this->getIdentifier($typeData->getAddress())][] = $typeData->getType()->getName();
            }
        }

        return $mapping;
    }
}
