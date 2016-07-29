<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface;

use OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\Config\AbstractPaymentConfigWithCountryAndCurrencyTest;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractPayflowGatewayConfigTest extends AbstractPaymentConfigWithCountryAndCurrencyTest
{
    /** @var PayflowGatewayConfigInterface */
    protected $config;

    public function testGetCredentials()
    {
        $expectedValues = [
            'test1',
            'test2',
            'test3',
            'test4'
        ];

        $this->configManager->expects($this->exactly(4))
            ->method('get')
            ->withConsecutive(
                [$this->getConfigKey($this->getConfigPrefix() . 'vendor')],
                [$this->getConfigKey($this->getConfigPrefix() . 'user')],
                [$this->getConfigKey($this->getConfigPrefix() . 'password')],
                [$this->getConfigKey($this->getConfigPrefix() . 'partner')]
            )->willReturnOnConsecutiveCalls(
                'test1',
                'test2',
                'test3',
                'test4'
            );

        $returnValues = $this->config->getCredentials();
        $this->assertCount(0, array_diff($returnValues, $expectedValues));
    }

    public function testIsTestMode()
    {
        $returnValue = true;
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'test_mode', $returnValue);
        $this->assertSame($returnValue, $this->config->isTestMode());
    }

    public function testGetPurchaseAction()
    {
        $returnValue = 'string';
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'payment_action', $returnValue);
        $this->assertSame($returnValue, $this->config->getPurchaseAction());
    }

    public function testIsZeroAmountAuthorizationEnabled()
    {
        $returnValue = true;
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'zero_amount_authorization', $returnValue);
        $this->assertSame($returnValue, $this->config->isZeroAmountAuthorizationEnabled());
    }

    public function testIsAuthorizationForRequiredAmountEnabled()
    {
        $returnValue = true;
        $this->setConfig(
            $this->once(),
            $this->getConfigPrefix() . 'authorization_for_required_amount',
            $returnValue
        );
        $this->assertSame($returnValue, $this->config->isAuthorizationForRequiredAmountEnabled());
    }

    public function testGetAllowedCreditCards()
    {
        $returnValue = ['Master Card', 'Visa'];
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'allowed_cc_types', $returnValue);
        $this->assertSame($returnValue, $this->config->getAllowedCreditCards());
    }

    public function testIsDebugModeEnabled()
    {
        $returnValue = true;
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'debug_mode', $returnValue);
        $this->assertSame($returnValue, $this->config->isDebugModeEnabled());
    }

    public function testIsUseProxyEnabled()
    {
        $returnValue = true;
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'use_proxy', $returnValue);
        $this->assertSame($returnValue, $this->config->isUseProxyEnabled());
    }

    public function testGetProxyHost()
    {
        $returnValue = 'proxy host';
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'proxy_host', $returnValue);
        $this->assertSame($returnValue, $this->config->getProxyHost());
    }

    public function testGetProxyPort()
    {
        $returnValue = 8099;
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'proxy_port', $returnValue);
        $this->assertSame($returnValue, $this->config->getProxyPort());
    }

    public function testIsSslVerificationEnabled()
    {
        $returnValue = true;
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'enable_ssl_verification', $returnValue);
        $this->assertSame($returnValue, $this->config->isSslVerificationEnabled());
    }

    public function testIsRequireCvvEntryEnabled()
    {
        $returnValue = true;
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'require_cvv', $returnValue);
        $this->assertSame($returnValue, $this->config->isRequireCvvEntryEnabled());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensionAlias()
    {
        return OroPayPalExtension::ALIAS;
    }
}
