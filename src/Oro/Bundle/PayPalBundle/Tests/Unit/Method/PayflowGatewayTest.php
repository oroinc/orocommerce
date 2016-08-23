<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method;

use Oro\Bundle\PayPalBundle\Method\PayflowGateway;

class PayflowGatewayTest extends AbstractPayflowGatewayTest
{
    /**
     * @return PayflowGateway
     */
    protected function getMethod()
    {
        return  new PayflowGateway($this->gateway, $this->paymentConfig, $this->router);
    }

    public function testGetType()
    {
        $this->assertEquals('payflow_gateway', $this->method->getType());
    }

    /** {@inheritdoc} */
    protected function getConfigPrefix()
    {
        return 'payflow_gateway_';
    }
}
