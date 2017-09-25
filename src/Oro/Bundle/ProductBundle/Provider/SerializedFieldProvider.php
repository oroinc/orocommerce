<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider as BaseSerializedFieldProvider;

class SerializedFieldProvider extends BaseSerializedFieldProvider
{
    /**
     * {@inheritDoc}
     */
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
