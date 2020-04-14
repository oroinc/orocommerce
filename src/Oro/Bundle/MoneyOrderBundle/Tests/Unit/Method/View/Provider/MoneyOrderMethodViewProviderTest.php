<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\View\Provider;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProviderInterface;
use Oro\Bundle\MoneyOrderBundle\Method\View\Factory\MoneyOrderPaymentMethodViewFactoryInterface;
use Oro\Bundle\MoneyOrderBundle\Method\View\Provider\MoneyOrderMethodViewProvider;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\View\Provider\AbstractMethodViewProviderTest;

class MoneyOrderMethodViewProviderTest extends AbstractMethodViewProviderTest
{
    protected function setUp(): void
    {
        $this->factory = $this->createMock(MoneyOrderPaymentMethodViewFactoryInterface::class);
        $this->configProvider = $this->createMock(MoneyOrderConfigProviderInterface::class);
        $this->paymentConfigClass = MoneyOrderConfigInterface::class;
        $this->provider = new MoneyOrderMethodViewProvider($this->configProvider, $this->factory);
    }
}
