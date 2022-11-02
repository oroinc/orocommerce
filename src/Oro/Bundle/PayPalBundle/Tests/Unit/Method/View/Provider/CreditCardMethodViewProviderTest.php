<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Tests\Unit\Method\View\Provider\AbstractMethodViewProviderTest;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\View\Factory\PayPalCreditCardPaymentMethodViewFactoryInterface;
use Oro\Bundle\PayPalBundle\Method\View\Provider\CreditCardMethodViewProvider;

class CreditCardMethodViewProviderTest extends AbstractMethodViewProviderTest
{
    protected function setUp(): void
    {
        $this->factory = $this->createMock(PayPalCreditCardPaymentMethodViewFactoryInterface::class);
        $this->configProvider = $this->createMock(PayPalCreditCardConfigProviderInterface::class);
        $this->paymentConfigClass = PayPalCreditCardConfigInterface::class;
        $this->provider = new CreditCardMethodViewProvider($this->factory, $this->configProvider);
    }
}
