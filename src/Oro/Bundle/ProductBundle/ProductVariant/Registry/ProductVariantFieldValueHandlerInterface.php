<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\Registry;

/**
 * Interface for product variant field handlers
 */
interface ProductVariantFieldValueHandlerInterface
{
    /**
     * Return available values for product variant fields
     */
    public function getPossibleValues(string $fieldName) : array;

    /**
     * Return scalar variant field value
     */
    public function getScalarValue(mixed $value) : mixed;

    /**
     * Return human-readable value of passed value
     */
    public function getHumanReadableValue(string $fieldName, mixed $value) : mixed;

    /**
     * Return handler type
     */
    public function getType() : string;
}
