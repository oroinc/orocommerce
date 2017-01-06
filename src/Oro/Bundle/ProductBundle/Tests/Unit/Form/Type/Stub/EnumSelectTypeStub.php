<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnumSelectTypeStub extends AbstractType
{
    const NAME = 'oro_enum_select';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'configs' => [],
            'enum_code' => null,
            'placeholder' => null,
            'disabled_values' => [],
            'excluded_values' => [],
        ]);

        $resolver->setAllowedTypes([
            'disabled_values' => ['array', 'callable'],
            'excluded_values' => ['array', 'callable'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
