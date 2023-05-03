<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config\AbstractPaymentConfigTestCase;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalConfigInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

abstract class AbstractPayPalConfigTest extends AbstractPaymentConfigTestCase
{
    /** @var PayPalConfigInterface */
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

    public function testGetCredentials()
    {
        $returnValue = [
                Option\Vendor::VENDOR => 'string',
                Option\User::USER => 'string',
                Option\Password::PASSWORD => 'string',
                Option\Partner::PARTNER => 'string'
        ];
        $this->assertSame($returnValue, $this->config->getCredentials());
    }
}
