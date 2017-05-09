<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class DataDescriptor implements OptionInterface
{
    const DATA_DESCRIPTOR = 'data_descriptor';

    /**
     * {@inheritdoc}
     */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(DataDescriptor::DATA_DESCRIPTOR)
            ->addAllowedTypes(DataDescriptor::DATA_DESCRIPTOR, 'string');
    }
}
