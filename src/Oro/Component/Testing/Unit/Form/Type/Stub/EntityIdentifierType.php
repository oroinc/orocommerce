<?php

namespace Oro\Component\Testing\Unit\Form\Type\Stub;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType as BaseEntityIdentifierType;

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
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'choice_list' => $this->choiceList,
                'class' => '',
            ]
        );
    }
}
