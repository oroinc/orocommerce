<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Action;

class DataUploadRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getAction()
    {
        return Action::DATA_UPLOAD;
    }
}
