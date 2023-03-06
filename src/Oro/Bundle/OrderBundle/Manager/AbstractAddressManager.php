<?php

namespace Oro\Bundle\OrderBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrderBundle\Provider\AddressProviderInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Base address manager class.
 */
class AbstractAddressManager
{
    const DELIMITER = '_';

    const ACCOUNT_LABEL = 'form.address.group_label.customer';
    const ACCOUNT_USER_LABEL = 'form.address.group_label.customer_user';

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
     * @param string $groupLabelPrefix
     * @return TypedOrderAddressCollection
     */
    public function getGroupedAddresses(CustomerOwnerAwareInterface $entity, $type, $groupLabelPrefix = 'oro.order.')
    {
        $addresses = [];

        $customer = $entity->getCustomer();
        if ($customer) {
            $customerGroupLabel = $groupLabelPrefix . static::ACCOUNT_LABEL;
            $customerAddresses = $this->addressProvider->getCustomerAddresses($customer, $type);
            foreach ($customerAddresses as $customerAddress) {
                $addresses[$customerGroupLabel][$this->getIdentifier($customerAddress)] = $customerAddress;
            }
        }

        $customerUser = $entity->getCustomerUser();
        if ($customerUser) {
            $customerUserGroupLabel = $groupLabelPrefix . static::ACCOUNT_USER_LABEL;
            $customerUserAddresses = $this->addressProvider->getCustomerUserAddresses($customerUser, $type);
            if ($customerUserAddresses) {
                foreach ($customerUserAddresses as $customerUserAddress) {
                    $addresses[$customerUserGroupLabel][$this->getIdentifier($customerUserAddress)] =
                        $customerUserAddress;
                }
            }
        }

        return new TypedOrderAddressCollection($customerUser, $type, $addresses);
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

        return sprintf('%s%s%s', $this->map->indexOf($className), static::DELIMITER, $address->getId());
    }

    /**
     * @param string $identifier
     * @return AbstractAddress
     */
    public function getEntityByIdentifier($identifier)
    {
        $identifierData = explode(static::DELIMITER, $identifier);
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
