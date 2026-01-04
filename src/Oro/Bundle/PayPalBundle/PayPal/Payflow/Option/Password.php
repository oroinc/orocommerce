<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class Password extends AbstractOption
{
    public const PASSWORD = 'PWD';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Password::PASSWORD)
            ->addAllowedTypes(Password::PASSWORD, 'string');
    }
}
