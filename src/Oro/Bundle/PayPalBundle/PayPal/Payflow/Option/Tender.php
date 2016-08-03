<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class Tender extends AbstractOption
{
    const TENDER = 'TENDER';

    const AUTOMATED_CLEARINGHOUSE = 'A';
    const CREDIT_CARD = 'C';
    const PINLESS_DEBIT = 'D';
    const TELECHECK = 'K';
    const PAYPAL = 'P';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Tender::TENDER)
            ->addAllowedValues(
                Tender::TENDER,
                [
                    Tender::AUTOMATED_CLEARINGHOUSE,
                    Tender::CREDIT_CARD,
                    Tender::PINLESS_DEBIT,
                    Tender::TELECHECK,
                    Tender::PAYPAL,
                ]
            );
    }
}
