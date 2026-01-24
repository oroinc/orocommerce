<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures password option for PayPal Payflow authentication.
 *
 * Manages the merchant password credential for PayPal Payflow API authentication.
 */
class Password extends AbstractOption
{
    const PASSWORD = 'PWD';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Password::PASSWORD)
            ->addAllowedTypes(Password::PASSWORD, 'string');
    }
}
