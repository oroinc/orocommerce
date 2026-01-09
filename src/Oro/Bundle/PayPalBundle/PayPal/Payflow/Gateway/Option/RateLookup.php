<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures rate lookup ID option for PayPal Payflow Gateway transactions.
 *
 * Manages the rate lookup identifier for recurring billing transactions.
 */
class RateLookup extends AbstractOption
{
    public const RATELOOKUPID = 'RATELOOKUPID';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(RateLookup::RATELOOKUPID)
            ->addAllowedTypes(RateLookup::RATELOOKUPID, 'string');
    }
}
