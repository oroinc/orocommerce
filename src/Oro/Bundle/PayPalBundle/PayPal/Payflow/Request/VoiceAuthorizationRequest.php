<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Transaction;

/**
 * Represents a voice authorization request for PayPal Payflow transactions.
 *
 * Handles voice authorization transaction type (not yet implemented).
 */
class VoiceAuthorizationRequest extends AbstractRequest
{
    #[\Override]
    public function getTransactionType()
    {
        throw new \BadMethodCallException(
            sprintf('Request type "%s" is not implemented yet', get_class($this))
        );

        return Transaction::VOICE_AUTHORIZATION;
    }
}
