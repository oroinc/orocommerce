<?php

namespace Oro\Bundle\ProductBundle\Provider;

class CustomVariantFieldProvider extends CustomFieldProvider
{
    protected $allowedFieldTypes = [
        'enum',
        'boolean',
    ];

    /**
     * {@inheritdoc}
     */
    protected function isFieldTypeAllowed($fieldType)
    {
        return in_array($fieldType, $this->allowedFieldTypes);
    }
}
