<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

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
