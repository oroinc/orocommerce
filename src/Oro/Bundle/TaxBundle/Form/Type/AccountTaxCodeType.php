<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

class AccountTaxCodeType extends AbstractTaxCodeType
{
    const NAME = 'orob2b_tax_account_tax_code_type';

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
