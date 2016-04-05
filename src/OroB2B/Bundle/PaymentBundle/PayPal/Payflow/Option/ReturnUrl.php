<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class ReturnUrl implements OptionInterface
{
    const RETURNURL = 'RETURNURL';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(ReturnUrl::RETURNURL)
            ->addAllowedTypes(ReturnUrl::RETURNURL, 'string');
    }
}
