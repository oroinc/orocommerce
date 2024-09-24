<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * PayPal delayed capture request
 */
class DelayedCaptureRequest extends AbstractRequest
{
    #[\Override]
    public function getTransactionType()
    {
        return Option\Transaction::DELAYED_CAPTURE;
    }

    #[\Override]
    public function configureRequestOptions()
    {
        $this
            ->addOption(new Option\Tender())
            ->addOption(new Option\Amount(false))
            ->addOption(new Option\CaptureComplete())
            ->addOption(new Option\OriginalTransaction())
            ->addOption(new Option\Verbosity())
            ->addOption(new Option\ButtonSource())
            ->addOption(new Option\Order());

        return $this;
    }
}
