<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures tender type option for PayPal Payflow transactions.
 *
 * Defines the payment method type (credit card, ACH, PayPal, etc.) for the transaction.
 */
class Tender extends AbstractOption
{
    const TENDER = 'TENDER';

    const AUTOMATED_CLEARINGHOUSE = 'A';
    const CREDIT_CARD = 'C';
    const PINLESS_DEBIT = 'D';
    const TELECHECK = 'K';
    const PAYPAL = 'P';

    #[\Override]
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
