<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\Extension\Core\Type\TextType as ParentTextType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TextType extends ParentTextType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults([
            'validation_groups' => ['Default'],
            'constraints' => []
        ]);
    }
}
