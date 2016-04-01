<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class Swipe extends AbstractOption
{
    const SWIPE = 'SWIPE';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Swipe::SWIPE)
            ->addAllowedTypes(Swipe::SWIPE, 'string');
    }
}
