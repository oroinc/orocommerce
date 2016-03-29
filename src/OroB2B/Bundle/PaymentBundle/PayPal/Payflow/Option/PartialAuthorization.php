<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

class PartialAuthorization extends AbstractOption
{
    const PARTIALAUTH = 'PARTIALAUTH';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(PartialAuthorization::PARTIALAUTH)
            ->addAllowedValues(PartialAuthorization::PARTIALAUTH, PartialAuthorization::YES);
    }
}
