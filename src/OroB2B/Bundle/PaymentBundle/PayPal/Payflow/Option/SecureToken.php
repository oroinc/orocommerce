<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

class SecureToken implements OptionInterface
{
    const CREATESECURETOKEN = 'CREATESECURETOKEN';
    const SECURETOKENID = 'SECURETOKENID';
    const SILENTTRAN = 'SILENTTRAN';

    const YES = 'Y';
    const TRUE = 'TRUE';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined([SecureToken::CREATESECURETOKEN, SecureToken::SECURETOKENID, SecureToken::SILENTTRAN])
            ->addAllowedValues(SecureToken::CREATESECURETOKEN, SecureToken::YES)
            ->addAllowedValues(SecureToken::SILENTTRAN, SecureToken::TRUE)
            ->addAllowedValues(Action::TRANSACTION_TYPE, [])
            ->addAllowedTypes(SecureToken::SECURETOKENID, 'string');
    }
}
