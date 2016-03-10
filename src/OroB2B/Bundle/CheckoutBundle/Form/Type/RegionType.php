<?php

namespace OroB2B\Bundle\CheckoutBundle\Form\Type;

class RegionType extends \Oro\Bundle\AddressBundle\Form\Type\RegionType
{
    public function getName()
    {
        return 'orob2b_region';
    }

    public function getParent()
    {
        return 'translatable_entity';
    }
}
