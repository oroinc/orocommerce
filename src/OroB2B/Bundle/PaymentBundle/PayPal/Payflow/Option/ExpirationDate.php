<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ExpirationDate extends AbstractOption
{
    const EXPDATE = 'EXPDATE';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(ExpirationDate::EXPDATE)
            ->addAllowedTypes(ExpirationDate::EXPDATE, 'string');
    }
}
