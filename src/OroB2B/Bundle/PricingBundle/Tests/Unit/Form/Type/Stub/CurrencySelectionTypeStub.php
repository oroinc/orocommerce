<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

class CurrencySelectionTypeStub extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'additional_currencies' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return CurrencySelectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'currency';
    }
}
