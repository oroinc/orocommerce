<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

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
