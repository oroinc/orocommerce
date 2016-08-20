<?php

namespace Oro\Bundle\MenuBundle\Tests\Unit\Form\Extension\Stub;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TooltipFormExtensionStub extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['tooltip']);
    }

    /**
     * {@inheritDoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
