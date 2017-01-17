<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config\AbstractPaymentConfigTestCase;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractPayPalCreditCardConfigTest extends AbstractPaymentConfigTestCase
{
    /**
     * @var PayPalCreditCardConfigInterface
     */
    protected $config;

    public function testIsTestMode()
    {
        $returnValue = true;
        $this->assertSame($returnValue, $this->config->isTestMode());
    }

    public function testGetPurchaseAction()
    {
        $returnValue = 'string';
        $this->assertSame($returnValue, $this->config->getPurchaseAction());
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
