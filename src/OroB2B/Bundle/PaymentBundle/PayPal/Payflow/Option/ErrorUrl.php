<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class ErrorUrl implements OptionInterface
{
    const ERRORURL = 'ERRORURL';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(ErrorUrl::ERRORURL)
            ->addAllowedTypes(ErrorUrl::ERRORURL, 'string');
    }
}
