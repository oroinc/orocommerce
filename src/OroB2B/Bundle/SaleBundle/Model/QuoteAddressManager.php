<?php

namespace OroB2B\Bundle\SaleBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Component\PropertyAccess\PropertyAccessor;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteAddress;
use OroB2B\Bundle\SaleBundle\Provider\QuoteAddressProvider;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;

class QuoteAddressManager
{
    const DELIMITER = '_';

    const ACCOUNT_LABEL = 'orob2b.account.entity_label';
    const ACCOUNT_USER_LABEL = 'orob2b.account.accountuser.entity_label';

    /**
     * @var QuoteAddressProvider
     */
    protected $quoteAddressProvider;

    /** @var ArrayCollection */
    protected $map;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $quoteAddressClass;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param QuoteAddressProvider $quoteAddressProvider
     * @param ManagerRegistry $registry
     * @param string $quoteAddressClass
     */
    public function __construct(
        QuoteAddressProvider $quoteAddressProvider,
        ManagerRegistry $registry,
        $quoteAddressClass
    ) {
        $this->quoteAddressProvider = $quoteAddressProvider;
        $this->registry = $registry;
        $this->quoteAddressClass = $quoteAddressClass;

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
     * @param QuoteAddress $quoteAddress
     *
     * @return QuoteAddress
     */
    public function updateFromAbstract(AbstractAddress $address = null, QuoteAddress $quoteAddress = null)
    {
        if (!$quoteAddress) {
            $quoteAddress = new $this->quoteAddressClass();
        }

        if ($address) {
            $addressClassName = ClassUtils::getClass($address);
            $addressMetadata = $this->registry->getManagerForClass($addressClassName)
                ->getClassMetadata($addressClassName);

            foreach ($addressMetadata->getFieldNames() as $fieldName) {
                $this->setValue($address, $quoteAddress, $fieldName);
            }

            foreach ($addressMetadata->getAssociationNames() as $associationName) {
                $this->setValue($address, $quoteAddress, $associationName);
            }
        }

        $quoteAddress->setAccountAddress(null);
        $quoteAddress->setAccountUserAddress(null);

        if ($address instanceof AccountAddress) {
            $quoteAddress->setAccountAddress($address);
        } elseif ($address instanceof AccountUserAddress) {
            $quoteAddress->setAccountUserAddress($address);
        }

        return $quoteAddress;
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
     * @param Quote $quote
     * @param string $type
     *
     * @return array
     */
    public function getGroupedAddresses(Quote $quote, $type)
    {
        $addresses = [];

        $account = $quote->getAccount();
        if ($account) {
            $accountAddresses = $this->quoteAddressProvider->getAccountAddresses($account, $type);
            foreach ($accountAddresses as $accountAddress) {
                $addresses[self::ACCOUNT_LABEL][$this->getIdentifier($accountAddress)] = $accountAddress;
            }
        }

        $accountUser = $quote->getAccountUser();
        if ($accountUser) {
            $accountUserAddresses = $this->quoteAddressProvider->getAccountUserAddresses($accountUser, $type);
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
     *
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
     *
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
