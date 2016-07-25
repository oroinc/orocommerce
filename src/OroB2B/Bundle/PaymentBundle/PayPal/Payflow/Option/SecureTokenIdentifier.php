<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class SecureTokenIdentifier extends AbstractOption
{
    const SECURETOKENID = 'SECURETOKENID';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(SecureTokenIdentifier::SECURETOKENID)
            ->addAllowedTypes(SecureTokenIdentifier::SECURETOKENID, 'string');
    }
}
