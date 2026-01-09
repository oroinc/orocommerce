<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures transaction type option for PayPal Payflow transactions.
 *
 * Defines the transaction type (authorization, sale, credit, void, etc.) for PayPal Payflow requests.
 */
class Transaction extends AbstractOption
{
    public const TRXTYPE = 'TRXTYPE';

    public const AUTHORIZATION = 'A';
    public const BALANCE_INQUIRY = 'B';
    public const CREDIT = 'C';
    public const DATA_UPLOAD = 'L';
    public const DELAYED_CAPTURE = 'D';
    public const DUPLICATE_TRANSACTION = 'N';
    public const INQUIRY = 'I';
    public const RATE_LOOKUP = 'K';
    public const SALE = 'S';
    public const VOICE_AUTHORIZATION = 'F';
    public const VOID = 'V';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Transaction::TRXTYPE)
            ->addAllowedValues(
                Transaction::TRXTYPE,
                [
                    Transaction::AUTHORIZATION,
                    Transaction::BALANCE_INQUIRY,
                    Transaction::CREDIT,
                    Transaction::DATA_UPLOAD,
                    Transaction::DELAYED_CAPTURE,
                    Transaction::DUPLICATE_TRANSACTION,
                    Transaction::INQUIRY,
                    Transaction::RATE_LOOKUP,
                    Transaction::SALE,
                    Transaction::VOICE_AUTHORIZATION,
                    Transaction::VOID,
                ]
            );
    }
}
