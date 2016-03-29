<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

class TransparentRedirect extends AbstractOption
{
    const SILENTTRAN = 'SILENTTRAN';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(TransparentRedirect::SILENTTRAN)
            ->addAllowedValues(TransparentRedirect::SILENTTRAN, TransparentRedirect::YES);
    }
}
