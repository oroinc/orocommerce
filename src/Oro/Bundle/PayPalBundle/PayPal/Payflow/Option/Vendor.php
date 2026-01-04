<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class Vendor extends AbstractOption
{
    public const VENDOR = 'VENDOR';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Vendor::VENDOR)
            ->addAllowedTypes(Vendor::VENDOR, 'string');
    }
}
