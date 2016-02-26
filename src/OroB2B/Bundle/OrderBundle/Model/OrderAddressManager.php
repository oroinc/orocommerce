<?php

namespace OroB2B\Bundle\OrderBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressProvider;

class OrderAddressManager extends AbstractAddressManager
{
    /** @var string */
    protected $orderAddressClass;

    /**
     * @param OrderAddressProvider $addressProvider
     * @param ManagerRegistry $registry
     * @param string $orderAddressClass
     */
    public function __construct(
        OrderAddressProvider $addressProvider,
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

        $orderAddress->setAccountAddress(null);
        $orderAddress->setAccountUserAddress(null);

        if ($address instanceof AccountAddress) {
            $orderAddress->setAccountAddress($address);
        } elseif ($address instanceof AccountUserAddress) {
            $orderAddress->setAccountUserAddress($address);
        }

        return $orderAddress;
    }
}
