<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class Transaction extends AbstractOption
{
    const TRXTYPE = 'TRXTYPE';

    const AUTHORIZATION = 'A';
    const BALANCE_INQUIRY = 'B';
    const CREDIT = 'C';
    const DATA_UPLOAD = 'L';
    const DELAYED_CAPTURE = 'D';
    const DUPLICATE_TRANSACTION = 'N';
    const INQUIRY = 'I';
    const RATE_LOOKUP = 'K';
    const SALE = 'S';
    const VOICE_AUTHORIZATION = 'F';
    const VOID = 'V';

    /** {@inheritdoc} */
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
