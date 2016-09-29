<?php

namespace Oro\Bundle\InfinitePayBundle\Action\PropertyAccessor;

use Oro\Bundle\InfinitePayBundle\Exception\ValueNotSetException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class CustomerPropertyAccessor
{
    const PROPERTY_PATH = 'vat_id';

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
     * @return string
     * @throws ValueNotSetException in account missing or invalid
     */
    public function extractVatId($object, $from = self::PROPERTY_PATH)
    {
        if ($object === null) {
            throw new \InvalidArgumentException('Object should not be empty');
        }

        try {
            $result = $this->propertyAccessor->getValue($object, $from);
        } catch (NoSuchPropertyException $e) {
            throw new ValueNotSetException('Object does not contain vat id');
        }

        if ($result === null) {
            throw new ValueNotSetException('Object value vat_id is not set');
        }

        return $result;
    }
}
