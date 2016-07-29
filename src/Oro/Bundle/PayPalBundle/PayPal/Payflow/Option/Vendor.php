<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class Vendor extends AbstractOption
{
    const VENDOR = 'VENDOR';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Vendor::VENDOR)
            ->addAllowedTypes(Vendor::VENDOR, 'string');
    }
}
