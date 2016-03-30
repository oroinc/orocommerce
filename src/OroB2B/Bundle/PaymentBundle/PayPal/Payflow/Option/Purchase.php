<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class Purchase extends AbstractOption
{
    const PONUM = 'PONUM';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Purchase::PONUM)
            ->addAllowedTypes(Purchase::PONUM, 'string');
    }
}
