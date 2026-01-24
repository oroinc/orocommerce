<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Transaction;

/**
 * Represents an inquiry request for PayPal Payflow transactions.
 *
 * Handles inquiry transaction type to retrieve transaction status (not yet implemented).
 */
class InquiryRequest extends AbstractRequest
{
    #[\Override]
    public function getTransactionType()
    {
        throw new \BadMethodCallException(
            sprintf('Request type "%s" is not implemented yet', get_class($this))
        );

        return Transaction::INQUIRY;
    }
}
