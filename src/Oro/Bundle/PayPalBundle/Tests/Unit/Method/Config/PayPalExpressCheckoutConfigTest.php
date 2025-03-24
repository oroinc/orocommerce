<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use PHPUnit\Framework\TestCase;

class PayPalExpressCheckoutConfigTest extends TestCase
{
    private PayPalExpressCheckoutConfig $config;

    #[\Override]
    protected function setUp(): void
    {
        $this->config = new PayPalExpressCheckoutConfig([
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
}
