<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

class CustomerTaxCodeType extends AbstractTaxCodeType
{
    public const NAME = 'oro_tax_customer_tax_code_type';

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
