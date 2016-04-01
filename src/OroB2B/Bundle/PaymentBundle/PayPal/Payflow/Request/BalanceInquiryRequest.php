<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Transaction;

class BalanceInquiryRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getAction()
    {
        throw new \BadMethodCallException(
            sprintf('Request type "%s" is not implemented yet', get_class($this))
        );

        return Transaction::BALANCE_INQUIRY;
    }
}
