<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Tests\Unit\Method\View\Provider\AbstractMethodViewProviderTest;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;
use Oro\Bundle\PaymentTermBundle\Method\View\Factory\PaymentTermPaymentMethodViewFactoryInterface;
use Oro\Bundle\PaymentTermBundle\Method\View\Provider\PaymentTermMethodViewProvider;

class PaymentTermMethodViewProviderTest extends AbstractMethodViewProviderTest
{
    protected function setUp(): void
    {
        $this->factory = $this->createMock(PaymentTermPaymentMethodViewFactoryInterface::class);
        $this->configProvider = $this->createMock(PaymentTermConfigProviderInterface::class);
        $this->paymentConfigClass = PaymentTermConfigInterface::class;
        $this->provider = new PaymentTermMethodViewProvider($this->factory, $this->configProvider);
    }
}
