<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

class CustomerTaxCodeType extends AbstractTaxCodeType
{
    const NAME = 'oro_tax_customer_tax_code_type';

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
