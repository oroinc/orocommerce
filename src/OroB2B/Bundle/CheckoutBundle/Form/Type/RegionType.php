<?php

namespace OroB2B\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\RegionType as BaseCountryType;

class RegionType extends BaseCountryType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'orob2b_region';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'translatable_entity';
    }
}
