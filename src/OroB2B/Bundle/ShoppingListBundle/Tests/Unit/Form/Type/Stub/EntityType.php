<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Type\Stub;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as BaseEntityTypeStub;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityType extends BaseEntityTypeStub
{
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(['class' => null]);
    }
}
