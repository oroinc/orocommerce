<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class IPAddress implements OptionInterface
{
    const CUSTIP = 'CUSTIP';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(IPAddress::CUSTIP)
            ->addAllowedTypes(IPAddress::CUSTIP, 'string');
    }
}
