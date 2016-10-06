<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

class ProductTaxCodeType extends AbstractTaxCodeType
{
    const NAME = 'oro_tax_product_tax_code_type';

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
        return self::NAME;
    }
}
