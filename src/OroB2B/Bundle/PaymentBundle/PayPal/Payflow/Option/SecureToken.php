<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

class SecureToken extends AbstractOption
{
    const SECURETOKEN = 'SECURETOKEN';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(SecureToken::SECURETOKEN)
            ->addAllowedTypes(SecureToken::SECURETOKEN, 'string');
    }
}
