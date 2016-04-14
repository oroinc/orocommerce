<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class SecureTokenIdentifier extends AbstractOption
{
    const SECURETOKENID = 'SECURETOKENID';

    /**
     * @return string
     */
    public static function generate()
    {
        return md5(microtime() . uniqid('Payflow', true));
    }

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(SecureTokenIdentifier::SECURETOKENID)
            ->addAllowedTypes(SecureTokenIdentifier::SECURETOKENID, 'string');
    }
}
