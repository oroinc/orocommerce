<?php

namespace OroB2B\Bundle\CheckoutBundle\Form\Type;

class CountryType extends \Oro\Bundle\AddressBundle\Form\Type\CountryType
{
    public function getName()
    {
        return 'orob2b_country';
    }

    public function getParent()
    {
        return 'translatable_entity';
    }
}
