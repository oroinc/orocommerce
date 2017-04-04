<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\Config\Provider\ApruveConfigProviderInterface;
use Oro\Bundle\ApruveBundle\Method\Factory\ApruvePaymentMethodFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\Provider\ApruvePaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider\AbstractMethodProviderTest;

class ApruvePaymentMethodProviderTest extends AbstractMethodProviderTest
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->factory = $this->createMock(ApruvePaymentMethodFactoryInterface::class);
        $this->configProvider = $this->createMock(ApruveConfigProviderInterface::class);
        $this->paymentConfigClass = ApruveConfigInterface::class;
        $this->methodProvider = new ApruvePaymentMethodProvider($this->configProvider, $this->factory);
    }
}
