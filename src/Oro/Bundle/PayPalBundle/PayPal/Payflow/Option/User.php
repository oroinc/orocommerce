<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures user option for PayPal Payflow authentication.
 *
 * Manages the merchant user credential for PayPal Payflow API authentication.
 */
class User extends AbstractOption
{
    const USER = 'USER';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(User::USER)
            ->addAllowedTypes(User::USER, 'string');
    }
}
