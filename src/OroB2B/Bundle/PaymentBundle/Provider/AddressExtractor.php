<?php

namespace OroB2B\Bundle\PaymentBundle\Provider;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;

class AddressExtractor
{
    const PROPERTY_PATH = 'billingAddress';

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param object|array $object
     * @param string $from
     * @return AddressInterface
     */
    public function extractAddress($object, $from = self::PROPERTY_PATH)
    {
        try {
            $result = $this->propertyAccessor->getValue($object, $from);
        } catch (NoSuchPropertyException $e) {
            throw new \InvalidArgumentException('Object does not contains billingAddress');
        }

        if ($result === null) {
            throw new \InvalidArgumentException('Object does not contains billingAddress');
        }

        return $result;
    }
}
