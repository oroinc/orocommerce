<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class PayflowGatewayTest extends AbstractPayflowGatewayTest
{
    /**
     * @return PayflowGateway
     */
    protected function getMethod()
    {
        return new PayflowGateway($this->gateway, $this->configManager, $this->router);
    }

    public function testGetType()
    {
        $this->assertEquals('payflow_gateway', $this->method->getType());
    }

    /**
     * {@inheritdoc}
     */
    protected function configureConfig(array $configs = [])
    {
        $configs = array_merge(
            [
                Configuration::PAYFLOW_GATEWAY_VENDOR_KEY => 'test_vendor',
                Configuration::PAYFLOW_GATEWAY_USER_KEY => 'test_user',
                Configuration::PAYFLOW_GATEWAY_PASSWORD_KEY => 'test_password',
                Configuration::PAYFLOW_GATEWAY_PARTNER_KEY => 'test_partner',
                Configuration::PAYFLOW_GATEWAY_TEST_MODE_KEY => true,
            ],
            $configs
        );

        parent::configureConfig($configs);
    }

    /** {@inheritdoc} */
    protected function getConfigPrefix()
    {
        return 'payflow_gateway_';
    }
}
