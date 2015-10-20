<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityVisibilityType extends TextType
{
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'ownership_disabled' => 'true',
            'website' => null,
        ]);
    }
}
