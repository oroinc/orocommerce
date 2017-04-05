<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\Registry;

interface ProductVariantFieldValueHandlerInterface
{
    /**
     * Return available values for product variant fields
     * @param string $variantFieldName
     * @return array
     */
    public function getPossibleValues($variantFieldName);

    /**
     * Return scalar variant field value
     * @param mixed $variantValue
     * @return mixed
     */
    public function getScalarValue($variantValue);

    /**
     * Return handler type
     * @return string
     */
    public function getType();
}
