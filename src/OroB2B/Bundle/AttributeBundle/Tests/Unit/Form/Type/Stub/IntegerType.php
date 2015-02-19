<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\Extension\Core\Type\IntegerType as ParentIntegerType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class IntegerType extends ParentIntegerType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(['type' => null, 'constraints' => []]);
    }
}
