<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Transaction;

/**
 * Represents a credit request for PayPal Payflow transactions.
 *
 * Handles credit transaction type to refund a previous sale (not yet implemented).
 */
class CreditRequest extends AbstractRequest
{
    #[\Override]
    public function getTransactionType()
    {
        throw new \BadMethodCallException(
            sprintf('Request type "%s" is not implemented yet', get_class($this))
        );

        return Transaction::CREDIT;
    }
}
