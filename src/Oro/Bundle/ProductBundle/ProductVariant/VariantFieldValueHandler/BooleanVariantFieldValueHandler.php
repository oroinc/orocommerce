<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler;

use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerInterface;

class BooleanVariantFieldValueHandler implements ProductVariantFieldValueHandlerInterface
{
    const TYPE = 'boolean';

    /**
     * {@inheritdoc}
     */
    public function getPossibleValues($variantFieldName)
    {
        return [0 => 'No', 1 => 'Yes'];
    }

    /**
     * {@inheritdoc}
     */
    public function getScalarValue($variantValue)
    {
        return (bool) $variantValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
