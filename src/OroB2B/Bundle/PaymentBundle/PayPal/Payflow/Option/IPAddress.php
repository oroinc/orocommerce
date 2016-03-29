<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

class IPAddress implements OptionInterface
{
    const CUSTIP = 'CUSTIP';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(IPAddress::CUSTIP)
            ->addAllowedValues(IPAddress::CUSTIP, 'string');
    }
}
