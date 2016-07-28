<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class Tender extends AbstractOption
{
    const TENDER = 'TENDER';

    const PAYPAL = 'P';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Tender::TENDER)
            ->addAllowedValues(Tender::TENDER, [Tender::PAYPAL]);
    }
}
