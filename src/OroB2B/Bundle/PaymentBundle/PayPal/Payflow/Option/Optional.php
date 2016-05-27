<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class Optional implements OptionInterface
{
    const USER1 = 'USER1';
    const USER2 = 'USER2';
    const USER3 = 'USER3';
    const USER4 = 'USER4';
    const USER5 = 'USER5';
    const USER6 = 'USER6';
    const USER7 = 'USER7';
    const USER8 = 'USER8';
    const USER9 = 'USER9';
    const USER10 = 'USER10';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(
                [
                    Optional::USER1,
                    Optional::USER2,
                    Optional::USER3,
                    Optional::USER4,
                    Optional::USER5,
                    Optional::USER6,
                    Optional::USER7,
                    Optional::USER8,
                    Optional::USER9,
                    Optional::USER10,
                ]
            )
            ->addAllowedTypes(Optional::USER1, 'string')
            ->addAllowedTypes(Optional::USER2, 'string')
            ->addAllowedTypes(Optional::USER3, 'string')
            ->addAllowedTypes(Optional::USER4, 'string')
            ->addAllowedTypes(Optional::USER5, 'string')
            ->addAllowedTypes(Optional::USER6, 'string')
            ->addAllowedTypes(Optional::USER7, 'string')
            ->addAllowedTypes(Optional::USER8, 'string')
            ->addAllowedTypes(Optional::USER9, 'string')
            ->addAllowedTypes(Optional::USER10, 'string');
    }
}
