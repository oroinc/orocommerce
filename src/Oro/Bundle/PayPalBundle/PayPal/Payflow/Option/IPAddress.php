<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class IPAddress implements OptionInterface
{
    public const CUSTIP = 'CUSTIP';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(IPAddress::CUSTIP)
            ->addAllowedTypes(IPAddress::CUSTIP, 'string');
    }
}
