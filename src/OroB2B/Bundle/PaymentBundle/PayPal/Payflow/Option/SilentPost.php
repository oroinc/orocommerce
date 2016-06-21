<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class SilentPost implements OptionInterface
{
    const SILENTPOSTURL = 'SILENTPOSTURL';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(SilentPost::SILENTPOSTURL)
            ->addAllowedTypes(SilentPost::SILENTPOSTURL, 'string');
    }
}
