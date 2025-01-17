<?php

namespace Oro\Bundle\OrderBundle\Manager;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\OrderBundle\Provider\AddressProviderInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The base class for address managers.
 */
class AbstractAddressManager
{
    protected const DELIMITER = '_';

    protected const CUSTOMER_LABEL = 'form.address.group_label.customer';
    protected const CUSTOMER_USER_LABEL = 'form.address.group_label.customer_user';

    private array $map = [];

    public function __construct(
        protected readonly AddressProviderInterface $addressProvider,
        protected readonly ManagerRegistry $doctrine,
        private readonly PropertyAccessorInterface $propertyAccessor
    ) {
    }

    public function addEntity(string $alias, string $className): void
    {
        $this->map[$alias] = $className;
    }

    public function getGroupedAddresses(
        CustomerOwnerAwareInterface $entity,
        string $type,
        string $groupLabelPrefix = 'oro.order.'
    ): TypedOrderAddressCollection {
        $addresses = [];

        $customer = $entity->getCustomer();
        if ($customer) {
            $customerGroupLabel = $groupLabelPrefix . static::CUSTOMER_LABEL;
            $customerAddresses = $this->addressProvider->getCustomerAddresses($customer, $type);
            foreach ($customerAddresses as $customerAddress) {
                $addresses[$customerGroupLabel][$this->getIdentifier($customerAddress)] = $customerAddress;
            }
        }

        $customerUser = $entity->getCustomerUser();
        if ($customerUser) {
            $customerUserGroupLabel = $groupLabelPrefix . static::CUSTOMER_USER_LABEL;
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

    public function getIdentifier(AbstractAddress $address): string
    {
        $className = ClassUtils::getClass($address);

        $index = array_search($className, $this->map, true);
        if (false === $index) {
            throw new \InvalidArgumentException(sprintf('Entity with "%s" not registered', $className));
        }

        return sprintf('%s%s%s', $index, static::DELIMITER, $address->getId());
    }

    public function getEntityByIdentifier(string $identifier): ?AbstractAddress
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
        if (!$alias || !isset($this->map[$alias])) {
            throw new \InvalidArgumentException(sprintf('Unknown alias "%s"', $alias));
        }

        $className = $this->map[$alias];

        return $this->doctrine->getManagerForClass($className)->find($className, (int)$id);
    }

    protected function copyAddress(AbstractAddress $from, AbstractAddress $to): void
    {
        $addressClassName = ClassUtils::getClass($from);
        $addressMetadata = $this->doctrine->getManagerForClass($addressClassName)
            ->getClassMetadata($addressClassName);
        foreach ($addressMetadata->getFieldNames() as $fieldName) {
            $this->setValue($from, $to, $fieldName);
        }
        foreach ($addressMetadata->getAssociationNames() as $associationName) {
            $this->setValue($from, $to, $associationName);
        }
    }

    protected function setValue(AbstractAddress $from, AbstractAddress $to, string $property): void
    {
        try {
            $value = $this->propertyAccessor->getValue($from, $property);
            if (!$this->isEmptyValue($value)) {
                if ($value instanceof \DateTimeInterface) {
                    $value = clone $value;
                }
                $this->propertyAccessor->setValue($to, $property, $value);
            }
        } catch (NoSuchPropertyException $e) {
        }
    }

    private function isEmptyValue(mixed $value): bool
    {
        return !$value || ($value instanceof Collection && $value->isEmpty());
    }
}
