<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider as BaseSerializedFieldProvider;

/**
 * Determines which product entity fields should be stored as serialized data.
 *
 * This provider extends the base serialized field logic with product-specific rules,
 * ensuring that boolean attributes used in configurable products are stored as table columns
 * rather than serialized data for proper variant field functionality.
 */
class SerializedFieldProvider extends BaseSerializedFieldProvider
{
    #[\Override]
    protected function isSerializableType(FieldConfigModel $fieldConfigModel)
    {
        $type = $fieldConfigModel->getType();
        $config = $fieldConfigModel->toArray('attribute');

        //boolean attribute should be always 'table column' to use it as field for product configurable products
        if ($type === 'boolean' && isset($config['is_attribute']) && $config['is_attribute'] === true) {
            return false;
        }

        return parent::isSerializableType($fieldConfigModel);
    }
}
