<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateSecureToken extends AbstractOption
{
    const CREATESECURETOKEN = 'CREATESECURETOKEN';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(CreateSecureToken::CREATESECURETOKEN)
            ->setDefault(CreateSecureToken::CREATESECURETOKEN, CreateSecureToken::YES)
            ->addAllowedValues(CreateSecureToken::CREATESECURETOKEN, CreateSecureToken::YES);
    }
}
