<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

class AccountGroupTaxCodeType extends AbstractTaxCodeType
{
    const NAME = 'orob2b_tax_account_group_tax_code_type';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
