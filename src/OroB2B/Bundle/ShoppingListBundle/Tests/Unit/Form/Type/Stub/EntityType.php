<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as BaseEntityTypeStub;

class EntityType extends BaseEntityTypeStub
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['class' => null]);
    }
}
