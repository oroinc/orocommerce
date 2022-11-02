<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProviderInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Factory\MoneyOrderPaymentMethodFactoryInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Provider\MoneyOrderMethodProvider;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider\AbstractMethodProviderTest;

class MoneyOrderMethodProviderTest extends AbstractMethodProviderTest
{
    protected function setUp(): void
    {
        $this->factory = $this->createMock(MoneyOrderPaymentMethodFactoryInterface::class);
        $this->configProvider = $this->createMock(MoneyOrderConfigProviderInterface::class);
        $this->paymentConfigClass = MoneyOrderConfigInterface::class;
        $this->methodProvider = new MoneyOrderMethodProvider($this->configProvider, $this->factory);
    }
}
