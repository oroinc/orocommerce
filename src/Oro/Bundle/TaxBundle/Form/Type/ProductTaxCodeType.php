<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

class ProductTaxCodeType extends AbstractTaxCodeType
{
    const NAME = 'oro_tax_product_tax_code_type';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
