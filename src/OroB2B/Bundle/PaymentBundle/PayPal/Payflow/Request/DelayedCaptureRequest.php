<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class DelayedCaptureRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getAction()
    {
        return Option\Transaction::DELAYED_CAPTURE;
    }

    /** {@inheritdoc} */
    public function configureRequestOptions()
    {
        $this
            ->addOption(new Option\Tender())
            ->addOption(new Option\Amount(false))
            ->addOption(new Option\SecureToken())
            ->addOption(new Option\OriginalTransaction())
            ->addOption(new Option\Verbosity());

        return $this;
    }
}
