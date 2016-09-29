<?php

namespace Oro\Bundle\InfinitePayBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Form\Type\TextType;

class VatIdType extends TextType
{
    const NAME = 'oro_infinitepay.form_type.vat_id';

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
        return static::NAME;
    }
}
