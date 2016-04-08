<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class Password extends AbstractOption
{
    const PASSWORD = 'PWD';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Password::PASSWORD)
            ->addAllowedTypes(Password::PASSWORD, 'string');
    }
}
