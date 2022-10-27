<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Tests\Unit\Method\View\Provider\AbstractMethodViewProviderTest;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalExpressCheckoutConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\View\Factory\PayPalExpressCheckoutPaymentMethodViewFactoryInterface;
use Oro\Bundle\PayPalBundle\Method\View\Provider\ExpressCheckoutMethodViewProvider;

class ExpressCheckoutMethodViewProviderTest extends AbstractMethodViewProviderTest
{
    protected function setUp(): void
    {
        $this->factory = $this->createMock(PayPalExpressCheckoutPaymentMethodViewFactoryInterface::class);
        $this->configProvider = $this->createMock(PayPalExpressCheckoutConfigProviderInterface::class);
        $this->paymentConfigClass = PayPalExpressCheckoutConfigInterface::class;
        $this->provider = new ExpressCheckoutMethodViewProvider($this->factory, $this->configProvider);
    }
}
