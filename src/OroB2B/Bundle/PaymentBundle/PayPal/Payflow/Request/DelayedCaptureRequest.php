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
    public function configureOptions()
    {
        $this
            ->addOption(new Option\Amount())
            ->addOption(new Option\SecureToken())
            ->addOption(new Option\OriginalTransaction())
            ->addOption(new Option\Verbosity());
    }
}
