<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Transaction;

/**
 * Represents a duplicate transaction request for PayPal Payflow transactions.
 *
 * Handles duplicate transaction type (not yet implemented).
 */
class DuplicateTransactionRequest extends AbstractRequest
{
    #[\Override]
    public function getTransactionType()
    {
        throw new \BadMethodCallException(
            sprintf('Request type "%s" is not implemented yet', get_class($this))
        );

        return Transaction::DUPLICATE_TRANSACTION;
    }
}
