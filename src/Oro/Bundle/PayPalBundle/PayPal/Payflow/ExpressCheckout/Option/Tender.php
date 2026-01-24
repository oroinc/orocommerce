<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures tender type option for PayPal Express Checkout transactions.
 *
 * Restricts tender type to PayPal for Express Checkout transactions,
 * ensuring only PayPal payment method is used.
 */
class Tender extends AbstractOption
{
    const TENDER = 'TENDER';

    const PAYPAL = 'P';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Tender::TENDER)
            ->addAllowedValues(Tender::TENDER, [Tender::PAYPAL]);
    }
}
