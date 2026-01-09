<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures card expiration date option for PayPal Payflow Gateway transactions.
 *
 * Manages the credit card expiration date in MMYY format.
 */
class ExpirationDate extends AbstractOption
{
    public const EXPDATE = 'EXPDATE';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(ExpirationDate::EXPDATE)
            ->addAllowedTypes(ExpirationDate::EXPDATE, 'string');
    }
}
