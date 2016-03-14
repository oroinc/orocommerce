<?php

namespace OroB2B\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\CountryType as BaseCountryType;

class CountryType extends BaseCountryType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'orob2b_country';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'translatable_entity';
    }
}
