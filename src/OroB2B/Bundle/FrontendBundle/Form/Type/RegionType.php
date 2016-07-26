<?php

namespace OroB2B\Bundle\FrontendBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

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
