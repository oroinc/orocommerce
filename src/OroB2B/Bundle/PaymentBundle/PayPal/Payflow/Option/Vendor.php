<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

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
