<?php

namespace Oro\Bundle\OrderBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\OrderBundle\Provider\AddressProviderInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;

class AbstractAddressManager
{
    const DELIMITER = '_';

    // TODO use oro.order.form.group_label.customer in the admin
    const ACCOUNT_LABEL = 'oro.frontend.order.form.group_label.customer';
    // TODO use oro.order.form.group_label.customer_user in the admin
    const ACCOUNT_USER_LABEL = 'oro.frontend.order.form.group_label.customer_user';

    /**
     * @var AddressProviderInterface
     */
    protected $addressProvider;

    /** @var ArrayCollection */
    protected $map;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param AddressProviderInterface $addressProvider
     * @param ManagerRegistry $registry
     */
    public function __construct(
        AddressProviderInterface $addressProvider,
        ManagerRegistry $registry
    ) {
        $this->addressProvider = $addressProvider;
        $this->registry = $registry;

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
     * @param CustomerOwnerAwareInterface $entity
     * @param string $type
     * @return array
     */
    public function getGroupedAddresses(CustomerOwnerAwareInterface $entity, $type)
    {
        $addresses = [];

        $customer = $entity->getCustomer();
        if ($customer) {
            $customerAddresses = $this->addressProvider->getCustomerAddresses($customer, $type);
            foreach ($customerAddresses as $customerAddress) {
                $addresses[self::ACCOUNT_LABEL][$this->getIdentifier($customerAddress)] = $customerAddress;
            }
        }

        $customerUser = $entity->getCustomerUser();
        if ($customerUser) {
            $customerUserAddresses = $this->addressProvider->getCustomerUserAddresses($customerUser, $type);
            if ($customerUserAddresses) {
                foreach ($customerUserAddresses as $customerUserAddress) {
                    $addresses[self::ACCOUNT_USER_LABEL][$this->getIdentifier($customerUserAddress)] =
                        $customerUserAddress;
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
