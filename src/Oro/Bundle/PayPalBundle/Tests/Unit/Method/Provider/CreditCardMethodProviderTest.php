<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider\AbstractMethodProviderTest;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\Factory\PayPalCreditCardPaymentMethodFactoryInterface;
use Oro\Bundle\PayPalBundle\Method\Provider\CreditCardMethodProvider;

class CreditCardMethodProviderTest extends AbstractMethodProviderTest
{
    protected function setUp(): void
    {
        $this->factory = $this->createMock(PayPalCreditCardPaymentMethodFactoryInterface::class);
        $this->configProvider = $this->createMock(PayPalCreditCardConfigProviderInterface::class);
        $this->paymentConfigClass = PayPalCreditCardConfigInterface::class;
        $this->methodProvider = new CreditCardMethodProvider($this->configProvider, $this->factory);
    }
}
