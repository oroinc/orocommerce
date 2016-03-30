<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class User extends AbstractOption
{
    const USER = 'USER';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(User::USER)
            ->addAllowedTypes(User::USER, 'string');
    }
}
