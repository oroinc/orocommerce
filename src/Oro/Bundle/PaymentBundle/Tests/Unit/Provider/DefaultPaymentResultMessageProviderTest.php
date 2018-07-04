<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\PaymentBundle\Provider\DefaultPaymentResultMessageProvider;

class DefaultPaymentResultMessageProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetErrorMessage()
    {
        $provider = new DefaultPaymentResultMessageProvider();
        $this->assertEquals('oro.payment.result.error', $provider->getErrorMessage());
    }
}
