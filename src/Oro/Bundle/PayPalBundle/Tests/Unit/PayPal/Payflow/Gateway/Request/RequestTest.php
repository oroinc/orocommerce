<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Request;

use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Request\RequestTest as BaseRequestTest;

class RequestTest extends BaseRequestTest
{
    /**
     * @return string
     */
    protected function getTestCasesDir()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'requests';
    }
}
