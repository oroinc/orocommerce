<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Request\Stub;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\AbstractRequest;

class AbstractRequestStub extends AbstractRequest
{
    /**
     * @return string
     */
    public function getTransactionType()
    {
        return 'some_action';
    }
}
