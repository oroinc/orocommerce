<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Component\Testing\Unit\EntityTrait;

class PayPalExpressCheckoutConfigTest extends AbstractPayPalConfigTest
{
    use EntityTrait;

    /** @var PayPalExpressCheckoutConfigInterface */
    protected $config;

    /**
     * {@inheritDoc}
     */
    protected function getPaymentConfig(): PaymentConfigInterface
    {
        $params = [
            PayPalExpressCheckoutConfig::FIELD_LABEL => 'test label',
            PayPalExpressCheckoutConfig::FIELD_SHORT_LABEL => 'test short label',
            PayPalExpressCheckoutConfig::FIELD_ADMIN_LABEL => 'test admin label',
            PayPalExpressCheckoutConfig::FIELD_PAYMENT_METHOD_IDENTIFIER => 'test_payment_method_identifier',
            PayPalExpressCheckoutConfig::TEST_MODE_KEY => true,
            PayPalExpressCheckoutConfig::PURCHASE_ACTION_KEY => 'string',
            PayPalExpressCheckoutConfig::CREDENTIALS_KEY => [
                Option\Vendor::VENDOR => 'string',
                Option\User::USER => 'string',
                Option\Password::PASSWORD => 'string',
                Option\Partner::PARTNER => 'string'
            ],
        ];

        return new PayPalExpressCheckoutConfig($params);
    }
}
