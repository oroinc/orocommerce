<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AclExtensionStub extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['loader']);
        $resolver->setDefaults(['loader' => function(){}]);
    }
}