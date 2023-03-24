<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Component\Testing\Unit\EntityTrait;

class PayPalCreditCardConfigTest extends AbstractPayPalConfigTest
{
    use EntityTrait;

    /** @var PayPalCreditCardConfigInterface */
    protected $config;

    /**
     * {@inheritDoc}
     */
    protected function getPaymentConfig(): PaymentConfigInterface
    {
        $params = [
            PayPalCreditCardConfig::FIELD_LABEL => 'test label',
            PayPalCreditCardConfig::FIELD_SHORT_LABEL => 'test short label',
            PayPalCreditCardConfig::FIELD_ADMIN_LABEL => 'test admin label',
            PayPalCreditCardConfig::FIELD_PAYMENT_METHOD_IDENTIFIER => 'test_payment_method_identifier',
            PayPalCreditCardConfig::PROXY_PORT_KEY => '8099',
            PayPalCreditCardConfig::PROXY_HOST_KEY => 'proxy host',
            PayPalCreditCardConfig::USE_PROXY_KEY => true,
            PayPalCreditCardConfig::TEST_MODE_KEY => true,
            PayPalCreditCardConfig::REQUIRE_CVV_ENTRY_KEY => true,
            PayPalCreditCardConfig::ENABLE_SSL_VERIFICATION_KEY => true,
            PayPalCreditCardConfig::DEBUG_MODE_KEY => true,
            PayPalCreditCardConfig::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY => true,
            PayPalCreditCardConfig::ZERO_AMOUNT_AUTHORIZATION_KEY => true,
            PayPalCreditCardConfig::ALLOWED_CREDIT_CARD_TYPES_KEY => ['Master Card', 'Visa'],
            PayPalCreditCardConfig::PURCHASE_ACTION_KEY => 'string',
            PayPalCreditCardConfig::CREDENTIALS_KEY => [
                Option\Vendor::VENDOR => 'string',
                Option\User::USER => 'string',
                Option\Password::PASSWORD => 'string',
                Option\Partner::PARTNER => 'string'
            ],
        ];

        return new PayPalCreditCardConfig($params);
    }

    public function testIsZeroAmountAuthorizationEnabled()
    {
        $returnValue = true;
        $this->assertSame($returnValue, $this->config->isZeroAmountAuthorizationEnabled());
    }

    public function testIsAuthorizationForRequiredAmountEnabled()
    {
        $returnValue = true;
        $this->assertSame($returnValue, $this->config->isAuthorizationForRequiredAmountEnabled());
    }

    public function testGetAllowedCreditCards()
    {
        $returnValue = ['Master Card', 'Visa'];
        $this->assertSame($returnValue, $this->config->getAllowedCreditCards());
    }

    public function testIsDebugModeEnabled()
    {
        $returnValue = true;
        $this->assertSame($returnValue, $this->config->isDebugModeEnabled());
    }

    public function testIsUseProxyEnabled()
    {
        $returnValue = true;
        $this->assertSame($returnValue, $this->config->isUseProxyEnabled());
    }

    public function testGetProxyHost()
    {
        $returnValue = 'proxy host';
        $this->assertSame($returnValue, $this->config->getProxyHost());
    }

    public function testGetProxyPort()
    {
        $returnValue = 8099;
        $this->assertSame($returnValue, $this->config->getProxyPort());
    }

    public function testIsSslVerificationEnabled()
    {
        $this->assertTrue($this->config->isSslVerificationEnabled());
    }

    public function testIsRequireCvvEntryEnabled()
    {
        $this->assertTrue($this->config->isRequireCvvEntryEnabled());
    }
}
