<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProvidersRegistry;

class PaymentMethodProvidersRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentMethodProvidersRegistry
     */
    private $providerRegistry;

    /**
     * @var PaymentMethodProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMethodProvider;


    public function setUp()
    {
        $this->paymentMethodProvider = $this->getMockBuilder(PaymentMethodProviderInterface::class)->getMock();

        $this->providerRegistry = new PaymentMethodProvidersRegistry();
        $this->providerRegistry->addProvider($this->paymentMethodProvider);
    }

    public function testGetPaymentMethodProviders()
    {
        static::assertEquals(
            [$this->paymentMethodProvider],
            $this->providerRegistry->getPaymentMethodProviders()
        );
    }
}
