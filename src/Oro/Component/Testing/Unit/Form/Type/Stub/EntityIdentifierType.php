<?php

namespace Oro\Component\Testing\Unit\Form\Type\Stub;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType as BaseEntityIdentifierType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityIdentifierType extends EntityType
{
    /**
     * @param array $choices
     */
    public function __construct(array $choices)
    {
        parent::__construct($choices, BaseEntityIdentifierType::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'class' => '',
            ]
        );
    }
}
