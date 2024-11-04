<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

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
