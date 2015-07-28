<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Type\Stub;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as BaseEntityTypeStub;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EntityType extends BaseEntityTypeStub
{
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(['class' => null]);
    }
}
