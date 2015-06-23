<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class StubEnumSelectType extends AbstractType
{
    const NAME = 'oro_enum_select';

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
