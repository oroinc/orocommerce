<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class RateLookup extends AbstractOption
{
    const RATELOOKUPID = 'RATELOOKUPID';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(RateLookup::RATELOOKUPID)
            ->addAllowedTypes(RateLookup::RATELOOKUPID, 'string');
    }
}
