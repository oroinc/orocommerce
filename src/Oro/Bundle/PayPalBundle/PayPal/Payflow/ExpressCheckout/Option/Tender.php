<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class Tender extends AbstractOption
{
    public const TENDER = 'TENDER';

    public const PAYPAL = 'P';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Tender::TENDER)
            ->addAllowedValues(Tender::TENDER, [Tender::PAYPAL]);
    }
}
