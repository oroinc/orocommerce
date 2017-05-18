<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class DataValue implements OptionInterface
{
    const DATA_VALUE = 'data_value';

    /**
     * {@inheritdoc}
     */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(DataValue::DATA_VALUE)
            ->addAllowedTypes(DataValue::DATA_VALUE, 'string');
    }
}
