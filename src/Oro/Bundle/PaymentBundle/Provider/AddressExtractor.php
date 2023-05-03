<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Try to extract address (AddressInterface object) by object and property
 */
class AddressExtractor
{
    const PROPERTY_PATH = 'billingAddress';

    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param object|array $object
     * @param string $from
     * @return AddressInterface
     * @throws \InvalidArgumentException in address missing or invalid
     */
    public function extractAddress($object, $from = self::PROPERTY_PATH)
    {
        if ($object === null) {
            throw new \InvalidArgumentException('Object should not be empty');
        }

        try {
            $result = $this->propertyAccessor->getValue($object, $from);
        } catch (NoSuchPropertyException $e) {
            throw new \InvalidArgumentException(sprintf('Object does not contains %s', $from));
        }

        if ($result === null) {
            throw new \InvalidArgumentException(sprintf('Object does not contains %s', $from));
        }

        if (!$result instanceof AddressInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    '"Oro\Bundle\LocaleBundle\Model\AddressInterface" expected, "%s" found',
                    is_object($result) ? get_class($result) : gettype($result)
                )
            );
        }

        return $result;
    }

    /**
     * @param object $entity
     * @return null|string
     */
    public function getCountryIso2($entity)
    {
        try {
            return $this->extractAddress($entity)->getCountryIso2();
        } catch (\InvalidArgumentException $e) {
        }

        return null;
    }
}
