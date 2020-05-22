<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider\AbstractMethodProviderTest;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalExpressCheckoutConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\Factory\PayPalExpressCheckoutPaymentMethodFactoryInterface;
use Oro\Bundle\PayPalBundle\Method\Provider\ExpressCheckoutMethodProvider;

class ExpressCheckoutMethodProviderTest extends AbstractMethodProviderTest
{
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(PayPalExpressCheckoutConfigProviderInterface::class);
        $this->factory = $this->createMock(PayPalExpressCheckoutPaymentMethodFactoryInterface::class);
        $this->paymentConfigClass = PayPalExpressCheckoutConfigInterface::class;
        $this->methodProvider = new ExpressCheckoutMethodProvider($this->configProvider, $this->factory);
    }
}
