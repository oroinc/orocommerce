<?php

namespace Oro\Bundle\FrontendBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\CountryType as BaseCountryType;

class CountryType extends BaseCountryType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
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
