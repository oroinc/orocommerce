<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StubEnumSelectType extends AbstractType
{
    const NAME = 'oro_enum_select';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'configs' => [],
            'enum_code' => null
        ]);
    }

    public function getName()
    {
        return self::NAME;
    }
}
