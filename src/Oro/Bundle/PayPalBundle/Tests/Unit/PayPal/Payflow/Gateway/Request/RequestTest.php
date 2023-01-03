<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Request;

use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Request\RequestTest as BaseRequestTest;

class RequestTest extends BaseRequestTest
{
    /**
     * {@inheritDoc}
     */
    protected function getTestCasesDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'requests';
    }
}
