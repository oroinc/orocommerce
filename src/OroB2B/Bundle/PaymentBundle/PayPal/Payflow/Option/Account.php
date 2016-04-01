<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class Account extends AbstractOption
{
    const ACCT = 'ACCT';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Account::ACCT)
            ->addAllowedTypes(Account::ACCT, 'string');
    }
}
