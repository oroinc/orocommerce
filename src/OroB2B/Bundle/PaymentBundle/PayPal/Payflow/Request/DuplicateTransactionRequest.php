<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Transaction;

class DuplicateTransactionRequest extends AbstractRequest
{
    private function __construct()
    {
    }

    /** {@inheritdoc} */
    public function setOptions(array $options = [])
    {
        throw new \BadMethodCallException();
    }

    /** {@inheritdoc} */
    public function getAction()
    {
        return Transaction::DUPLICATE_TRANSACTION;
    }
}
