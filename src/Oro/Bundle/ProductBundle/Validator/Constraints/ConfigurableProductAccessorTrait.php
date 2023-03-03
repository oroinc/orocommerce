<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Get configurable product by given value and constraint with possibly configured path to configurable product.
 *
 * @property PropertyAccessorInterface $propertyAccessor
 */
trait ConfigurableProductAccessorTrait
{
    /**
     * @param mixed $value
     * @param Constraint $constraint
     * @return Product|null
     */
    protected function getConfigurableProduct($value, Constraint $constraint)
    {
        if ($constraint->property) {
            if (!$this->propertyAccessor->isReadable($value, $constraint->property)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Could not access property "%s" for class "%s"',
                        $constraint->property,
                        $this->getValueType($value)
                    )
                );
            }
            $value = $this->propertyAccessor->getValue($value, $constraint->property);
        }
        if (!$value) {
            return null;
        }

        if ($value && !is_a($value, Product::class)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Entity must be instance of "%s", "%s" given',
                    Product::class,
                    $this->getValueType($value)
                )
            );
        }

        if (!$value->isConfigurable()) {
            return null;
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function getValueType($value)
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }
}
