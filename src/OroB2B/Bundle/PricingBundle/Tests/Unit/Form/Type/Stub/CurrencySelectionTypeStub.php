<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

class CurrencySelectionTypeStub extends AbstractType
{
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
