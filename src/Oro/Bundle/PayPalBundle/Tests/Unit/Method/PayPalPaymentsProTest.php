<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method;

use Oro\Bundle\PayPalBundle\Method\PayPalPaymentsPro;

class PayPalPaymentsProTest extends AbstractPayflowGatewayTest
{
    /**
     * @return PayPalPaymentsPro
     */
    protected function getMethod()
    {
        return new PayPalPaymentsPro($this->gateway, $this->paymentConfig, $this->router);
    }

    /** {@inheritdoc} */
    protected function getConfigPrefix()
    {
        return 'paypal_payments_pro_';
    }

    public function testGetType()
    {
        $this->assertEquals('paypal_payments_pro', $this->method->getType());
    }

    public function testRequiresVerification()
    {
        $this->assertTrue($this->method->requiresVerification());
    }
}
