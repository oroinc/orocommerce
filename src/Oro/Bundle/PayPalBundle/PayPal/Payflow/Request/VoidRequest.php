<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Represents a void request for PayPal Payflow transactions.
 *
 * Handles void transaction type to cancel a previous authorization or sale.
 */
class VoidRequest extends AbstractRequest
{
    #[\Override]
    public function getTransactionType()
    {
        return Option\Transaction::VOID;
    }

    #[\Override]
    public function configureRequestOptions()
    {
        $this
            ->addOption(new Option\Tender())
            ->addOption(new Option\OriginalTransaction())
            ->addOption(new Option\Verbosity());

        return $this;
    }
}
