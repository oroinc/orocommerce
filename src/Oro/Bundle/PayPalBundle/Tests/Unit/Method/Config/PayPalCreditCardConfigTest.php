<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PayPalCreditCardConfigTest extends TestCase
{
    private PayPalCreditCardConfig $config;

    #[\Override]
    protected function setUp(): void
    {
        $this->config = new PayPalCreditCardConfig([
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
            ]
        ]);
    }

    public function testGetLabel(): void
    {
        self::assertSame('test label', $this->config->getLabel());
    }

    public function testGetShortLabel(): void
    {
        self::assertSame('test short label', $this->config->getShortLabel());
    }

    public function testGetAdminLabel(): void
    {
        self::assertSame('test admin label', $this->config->getAdminLabel());
    }

    public function testGetPaymentMethodIdentifier(): void
    {
        self::assertSame('test_payment_method_identifier', $this->config->getPaymentMethodIdentifier());
    }

    public function testIsTestMode(): void
    {
        self::assertTrue($this->config->isTestMode());
    }

    public function testGetPurchaseAction(): void
    {
        self::assertSame('string', $this->config->getPurchaseAction());
    }

    public function testGetCredentials(): void
    {
        self::assertSame(
            [
                Option\Vendor::VENDOR => 'string',
                Option\User::USER => 'string',
                Option\Password::PASSWORD => 'string',
                Option\Partner::PARTNER => 'string'
            ],
            $this->config->getCredentials()
        );
    }

    public function testIsZeroAmountAuthorizationEnabled(): void
    {
        self::assertTrue($this->config->isZeroAmountAuthorizationEnabled());
    }

    public function testIsAuthorizationForRequiredAmountEnabled(): void
    {
        self::assertTrue($this->config->isAuthorizationForRequiredAmountEnabled());
    }

    public function testGetAllowedCreditCards(): void
    {
        self::assertSame(['Master Card', 'Visa'], $this->config->getAllowedCreditCards());
    }

    public function testIsDebugModeEnabled(): void
    {
        self::assertTrue($this->config->isDebugModeEnabled());
    }

    public function testIsUseProxyEnabled(): void
    {
        self::assertTrue($this->config->isUseProxyEnabled());
    }

    public function testGetProxyHost(): void
    {
        self::assertSame('proxy host', $this->config->getProxyHost());
    }

    public function testGetProxyPort(): void
    {
        self::assertSame(8099, $this->config->getProxyPort());
    }

    public function testIsSslVerificationEnabled(): void
    {
        self::assertTrue($this->config->isSslVerificationEnabled());
    }

    public function testIsRequireCvvEntryEnabled(): void
    {
        self::assertTrue($this->config->isRequireCvvEntryEnabled());
    }
}
