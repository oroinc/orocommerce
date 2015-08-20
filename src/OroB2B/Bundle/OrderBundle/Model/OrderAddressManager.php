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

use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressProvider;

class OrderAddressManager
{
    const DELIMITER = '_';

    const ACCOUNT_LABEL = 'orob2b.account.entity_label';
    const ACCOUNT_USER_LABEL = 'orob2b.account.accountuser.entity_label';

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

    /**
     * @param AbstractAddress $from
     * @param AbstractAddress $to
     * @param string $property
     */
    protected function setValue(AbstractAddress $from, AbstractAddress $to, $property)
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        try {
            $value = $this->propertyAccessor->getValue($from, $property);
            if (!$value || ($value instanceof Collection && $value->isEmpty())) {
                return;
            }

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

        $account = $order->getAccount();
        if ($account) {
            $accountAddresses = $this->orderAddressProvider->getAccountAddresses($account, $type);
            foreach ($accountAddresses as $accountAddress) {
                $addresses[self::ACCOUNT_LABEL][$this->getIdentifier($accountAddress)] = $accountAddress;
            }
        }

        $accountUser = $order->getAccountUser();
        if ($accountUser) {
            $accountUserAddresses = $this->orderAddressProvider->getAccountUserAddresses($accountUser, $type);
            if ($accountUserAddresses) {
                foreach ($accountUserAddresses as $accountUserAddress) {
                    $addresses[self::ACCOUNT_USER_LABEL][$this->getIdentifier($accountUserAddress)] =
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
        $identifierData = explode(self::DELIMITER, $identifier);
        if (empty($identifierData[1]) || !empty($identifierData[2])) {
            throw new \InvalidArgumentException(sprintf('Wrong identifier "%s"', $identifier));
        }

        $id = $identifierData[1];
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException(sprintf('Wrong entity id "%s"', $id));
        }

        $alias = $identifierData[0];
        if (!$alias || !$this->map->containsKey($alias)) {
            throw new \InvalidArgumentException(sprintf('Unknown alias "%s"', $alias));
        }

        $className = $this->map->get($alias);

        return $this->registry->getManagerForClass($className)->find($className, (int)$id);
    }
}
