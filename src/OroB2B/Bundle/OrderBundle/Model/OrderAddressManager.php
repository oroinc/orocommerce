<?php

namespace OroB2B\Bundle\OrderBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Component\PropertyAccess\PropertyAccessor;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressProvider;

class OrderAddressManager
{
    const DELIMITER = '_';

    /**
     * @var OrderAddressProvider
     */
    protected $orderAddressProvider;

    /** @var ArrayCollection */
    protected $map;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $orderAddressClass;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param OrderAddressProvider $orderAddressProvider
     * @param ManagerRegistry $registry
     * @param string $orderAddressClass
     */
    public function __construct(
        OrderAddressProvider $orderAddressProvider,
        ManagerRegistry $registry,
        $orderAddressClass
    ) {
        $this->orderAddressProvider = $orderAddressProvider;
        $this->registry = $registry;
        $this->orderAddressClass = $orderAddressClass;

        $this->map = new ArrayCollection();
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param string $alias
     * @param string $className
     */
    public function addEntity($alias, $className)
    {
        $this->map->set($alias, $className);
    }

    /**
     * @param AbstractAddress $address
     * @param OrderAddress $orderAddress
     * @return OrderAddress
     */
    public function updateFromAbstract(AbstractAddress $address, OrderAddress $orderAddress = null)
    {
        if (!$orderAddress) {
            $orderAddress = new $this->orderAddressClass();
        }

        $existingClassName = ClassUtils::getClass($address);
        $metadata = $this->registry->getManagerForClass($existingClassName)->getClassMetadata($existingClassName);
        $orderMetadata = $this->registry->getManagerForClass($this->orderAddressClass)
            ->getClassMetadata($this->orderAddressClass);

        foreach ($metadata->getFieldNames() as $fieldName) {
            if ($orderMetadata->hasField($fieldName)) {
                $this->setValue($orderAddress, $address, $fieldName);
            }
        }

        foreach ($metadata->getAssociationNames() as $associationName) {
            if ($orderMetadata->hasAssociation($associationName)) {
                $this->setValue($orderAddress, $address, $associationName);
            }
        }

        return $orderAddress;
    }

    /**
     * @param AbstractAddress $from
     * @param AbstractAddress $to
     * @param string $property
     */
    protected function setValue(AbstractAddress $from, AbstractAddress $to, $property)
    {
        $value = $this->propertyAccessor->getValue($from, $property);
        if (!$value || ($value instanceof Collection && $value->isEmpty())) {
            return;
        }

        try {
            $this->propertyAccessor->setValue($to, $property, $value);
        } catch (NoSuchPropertyException $e) {
        }
    }

    /**
     * @param Order $order
     * @param string $type
     * @return array
     */
    public function getGroupedAddresses(Order $order, $type)
    {
        $addresses = [];
        $accountUser = $order->getAccountUser();
        if ($accountUser) {
            if ($accountUser->getAccount()) {
                $accountAddresses = $this->orderAddressProvider->getAccountAddresses(
                    $accountUser->getAccount(),
                    $type
                );
                foreach ($accountAddresses as $accountAddress) {
                    $addresses['orob2b.account.entity_label'][$this->getIdentifier($accountAddress)] =
                        $accountAddress;
                }
            }

            $accountUserAddresses = $this->orderAddressProvider->getAccountUserAddresses($accountUser, $type);
            if ($accountUserAddresses) {
                foreach ($accountUserAddresses as $accountUserAddress) {
                    $addresses['orob2b.accountuser.entity_label'][$this->getIdentifier($accountUserAddress)] =
                        $accountUserAddress;
                }
            }
        }

        return $addresses;
    }

    /**
     * @param AbstractAddress $address
     * @return string
     */
    public function getIdentifier(AbstractAddress $address)
    {
        $className = ClassUtils::getClass($address);

        if (!$this->map->contains($className)) {
            throw new \InvalidArgumentException(sprintf('Entity with "%s" not registered', $className));
        }

        return sprintf('%s%s%s', $this->map->indexOf($className), self::DELIMITER, $address->getId());
    }

    /**
     * @param string $identifier
     * @return AbstractAddress
     */
    public function getEntityByIdentifier($identifier)
    {
        list($alias, $id) = explode(self::DELIMITER, $identifier);

        if (!$this->map->containsKey($alias)) {
            throw new \InvalidArgumentException(sprintf('Unknown alias "%s"', $alias));
        }

        $className = $this->map->get($alias);

        return $this->registry->getManagerForClass($className)->find($className, $id);
    }
}
