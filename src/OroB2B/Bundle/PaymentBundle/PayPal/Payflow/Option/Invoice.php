<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class Invoice extends AbstractOption
{
    const INVNUM = 'INVNUM';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Invoice::INVNUM)
            ->addAllowedTypes(Invoice::INVNUM, 'string');
    }
}
