<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Request\Stub;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request\AbstractRequest;

class AbstractRequestStub extends AbstractRequest
{
    /**
     * @return string
     */
    public function getAction()
    {
        return 'some_action';
    }
}
