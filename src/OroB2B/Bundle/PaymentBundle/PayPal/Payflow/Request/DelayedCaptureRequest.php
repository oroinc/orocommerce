<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Action;

class DelayedCaptureRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getAction()
    {
        return Action::DELAYED_CAPTURE;
    }
}
