<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures vendor option for PayPal Payflow authentication.
 *
 * Manages the merchant vendor credential for PayPal Payflow API authentication.
 */
class Vendor extends AbstractOption
{
    const VENDOR = 'VENDOR';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Vendor::VENDOR)
            ->addAllowedTypes(Vendor::VENDOR, 'string');
    }
}
