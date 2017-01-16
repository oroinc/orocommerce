<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProvidersRegistry;

class PaymentMethodProvidersRegistryTest extends \PHPUnit_Framework_TestCase
{
    const PAYMENT_METHOD_PROVIDER_ID_1 = 'pvoder_1';

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
        $this->paymentMethodProvider
            ->expects(static::any())
            ->method('getType')
            ->willReturn(self::PAYMENT_METHOD_PROVIDER_ID_1);

        $this->providerRegistry = new PaymentMethodProvidersRegistry();
        $this->providerRegistry->addProvider($this->paymentMethodProvider);
    }

    public function testHasPaymentMethodProviderNo()
    {
        static::assertFalse($this->providerRegistry->hasPaymentMethodProvider('provider_not_existed'));
    }

    public function testHasPaymentMethodProviderYes()
    {
        static::assertTrue($this->providerRegistry->hasPaymentMethodProvider(self::PAYMENT_METHOD_PROVIDER_ID_1));
    }

    public function testGetPaymentMethodProviderNo()
    {
        static::assertNull($this->providerRegistry->getPaymentMethodProvider('provider_not_existed'));
    }

    public function testGetPaymentMethodProviderYes()
    {
        static::assertEquals(
            $this->paymentMethodProvider,
            $this->providerRegistry->getPaymentMethodProvider(self::PAYMENT_METHOD_PROVIDER_ID_1)
        );
    }

    public function testGetPaymentMethodProviders()
    {
        static::assertEquals(
            [self::PAYMENT_METHOD_PROVIDER_ID_1 => $this->paymentMethodProvider],
            $this->providerRegistry->getPaymentMethodProviders()
        );
    }
}
