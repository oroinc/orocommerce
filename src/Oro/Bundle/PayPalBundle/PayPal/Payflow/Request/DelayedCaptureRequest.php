<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class DelayedCaptureRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getTransactionType()
    {
        return Option\Transaction::DELAYED_CAPTURE;
    }

    /** {@inheritdoc} */
    public function configureRequestOptions()
    {
        $this
            ->addOption(new Option\Tender())
            ->addOption(new Option\Amount(false))
            ->addOption(new Option\CaptureComplete())
            ->addOption(new Option\OriginalTransaction())
            ->addOption(new Option\Verbosity());

        return $this;
    }
}
