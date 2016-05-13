<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Method\PayPalPaymentsPro;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class PayPalPaymentsProTest extends AbstractPayflowGatewayTest
{
    /**
     * @return PayPalPaymentsPro
     */
    protected function getMethod()
    {
        return new PayPalPaymentsPro($this->gateway, $this->configManager, $this->router);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureConfig(array $configs = [])
    {
        $configs = array_merge(
            [
                Configuration::PAYPAL_PAYMENTS_PRO_VENDOR_KEY => 'test_vendor',
                Configuration::PAYPAL_PAYMENTS_PRO_USER_KEY => 'test_user',
                Configuration::PAYPAL_PAYMENTS_PRO_PASSWORD_KEY => 'test_password',
                Configuration::PAYPAL_PAYMENTS_PRO_PARTNER_KEY => 'test_partner',
                Configuration::PAYPAL_PAYMENTS_PRO_TEST_MODE_KEY => true,
            ],
            $configs
        );

        parent::configureConfig($configs);
    }

    /** {@inheritdoc} */
    protected function getConfigPrefix()
    {
        return 'paypal_payments_pro_';
    }
}
