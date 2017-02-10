<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistry;

class PaymentMethodProvidersRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentMethodProvidersRegistry
     */
    private $providerRegistry;

    public function setUp()
    {
        $this->providerRegistry = new PaymentMethodProvidersRegistry();
    }

    public function testAddGetPaymentMethodProviders()
    {
        $providers = [
            $this->createMock(PaymentMethodProviderInterface::class),
            $this->createMock(PaymentMethodProviderInterface::class),
        ];

        foreach ($providers as $provider) {
            $this->providerRegistry->addProvider($provider);
        }

        static::assertSame(
            $providers,
            $this->providerRegistry->getPaymentMethodProviders()
        );
    }
}
