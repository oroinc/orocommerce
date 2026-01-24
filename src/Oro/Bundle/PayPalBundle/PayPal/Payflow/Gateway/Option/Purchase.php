<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures purchase order number option for PayPal Payflow Gateway transactions.
 *
 * Allows storing a purchase order number for transaction reference and tracking.
 */
class Purchase extends AbstractOption
{
    const PONUM = 'PONUM';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Purchase::PONUM)
            ->addAllowedTypes(Purchase::PONUM, 'string');
    }
}
