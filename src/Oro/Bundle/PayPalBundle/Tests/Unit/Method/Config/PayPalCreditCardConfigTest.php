<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalCreditCardConfigTest extends AbstractPayPalCreditCardConfigTest
{
    use EntityTrait;

    /**
     * {@inheritdoc}
     */
    protected function getPaymentConfig()
    {
        $bag = [
            PayPalSettings::CREDIT_CARD_LABELS_KEY => 'test label',
            PayPalSettings::CREDIT_CARD_SHORT_LABELS_KEY => 'test short label',
            PayPalSettings::PROXY_PORT_KEY => '8099',
            PayPalSettings::PROXY_HOST_KEY => 'proxy host',
            PayPalSettings::USE_PROXY_KEY => true,
            PayPalSettings::TEST_MODE_KEY => true,
            PayPalSettings::REQUIRE_CVV_ENTRY_KEY => true,
            PayPalSettings::ENABLE_SSL_VERIFICATION_KEY => true,
            PayPalSettings::DEBUG_MODE_KEY => true,
            PayPalSettings::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY => true,
            PayPalSettings::ZERO_AMOUNT_AUTHORIZATION_KEY => true,
            PayPalSettings::ALLOWED_CREDIT_CARD_TYPES_KEY => ['Master Card', 'Visa'],
            PayPalSettings::CREDIT_CARD_PAYMENT_ACTION_KEY => 'string',
            PayPalSettings::VENDOR_KEY => 'string',
            PayPalSettings::USER_KEY => 'string',
            PayPalSettings::PASSWORD_KEY => 'string',
            PayPalSettings::PARTNER_KEY => 'string',
            PayPalCreditCardConfig::ADMIN_LABEL_KEY => 'test admin label',
            PayPalCreditCardConfig::PAYMENT_METHOD_IDENTIFIER_KEY => 'test_payment_method_identifier'
        ];
        $settingsBag = $this->createMock(ParameterBag::class);
        $settingsBag->expects(static::any())->method('get')->willReturnCallback(
            function () use ($bag) {
                $args = func_get_args();
                return $bag[$args[0]];
            }
        );

        return new PayPalCreditCardConfig($settingsBag);
    }
}
