<?php

namespace OroB2B\Bundle\OrderBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use OroB2B\Bundle\OrderBundle\Provider\AddressProviderInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Component\PropertyAccess\PropertyAccessor;

use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;

class AbstractAddressManager
{
    const DELIMITER = '_';

    // TODO use orob2b.order.form.group_label.account in the admin
    const ACCOUNT_LABEL = 'orob2b.frontend.order.form.group_label.account';
    // TODO use orob2b.order.form.group_label.account_user in the admin
    const ACCOUNT_USER_LABEL = 'orob2b.frontend.order.form.group_label.account_user';

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
     * @param AccountOwnerAwareInterface $entity
     * @param string $type
     * @return array
     */
    public function getGroupedAddresses(AccountOwnerAwareInterface $entity, $type)
    {
        $addresses = [];

        $account = $entity->getAccount();
        if ($account) {
            $accountAddresses = $this->addressProvider->getAccountAddresses($account, $type);
            foreach ($accountAddresses as $accountAddress) {
                $addresses[self::ACCOUNT_LABEL][$this->getIdentifier($accountAddress)] = $accountAddress;
            }
        }

        $accountUser = $entity->getAccountUser();
        if ($accountUser) {
            $accountUserAddresses = $this->addressProvider->getAccountUserAddresses($accountUser, $type);
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
