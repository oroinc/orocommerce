<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class Action
{
    const TRANSACTION_TYPE = 'TRXTYPE';

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
}
