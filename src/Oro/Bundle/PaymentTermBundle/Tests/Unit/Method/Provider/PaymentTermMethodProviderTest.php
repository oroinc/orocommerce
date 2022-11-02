<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider\AbstractMethodProviderTest;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;
use Oro\Bundle\PaymentTermBundle\Method\Factory\PaymentTermPaymentMethodFactoryInterface;
use Oro\Bundle\PaymentTermBundle\Method\Provider\PaymentTermMethodProvider;

class PaymentTermMethodProviderTest extends AbstractMethodProviderTest
{
    protected function setUp(): void
    {
        $this->factory = $this->createMock(PaymentTermPaymentMethodFactoryInterface::class);
        $this->configProvider = $this->createMock(PaymentTermConfigProviderInterface::class);
        $this->paymentConfigClass = PaymentTermConfigInterface::class;
        $this->methodProvider = new PaymentTermMethodProvider($this->configProvider, $this->factory);
    }
}
