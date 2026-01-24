<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Transaction;

/**
 * Represents a rate lookup request for PayPal Payflow transactions.
 *
 * Handles rate lookup transaction type for recurring billing (not yet implemented).
 */
class RateLookupRequest extends AbstractRequest
{
    #[\Override]
    public function getTransactionType()
    {
        throw new \BadMethodCallException(
            sprintf('Request type "%s" is not implemented yet', get_class($this))
        );

        return Transaction::RATE_LOOKUP;
    }
}
