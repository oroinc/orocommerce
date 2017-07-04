<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\Registry;

interface ProductVariantFieldValueHandlerInterface
{
    /**
     * Return available values for product variant fields
     *
     * @param string $fieldName
     * @return array
     */
    public function getPossibleValues($fieldName);

    /**
     * Return scalar variant field value
     *
     * @param mixed $value
     * @return mixed
     */
    public function getScalarValue($value);

    /**
     * Return human-readable value of passed value
     *
     * @param string $fieldName
     * @param mixed $value
     * @return mixed
     */
    public function getHumanReadableValue($fieldName, $value);

    /**
     * Return handler type
     *
     * @return string
     */
    public function getType();
}
