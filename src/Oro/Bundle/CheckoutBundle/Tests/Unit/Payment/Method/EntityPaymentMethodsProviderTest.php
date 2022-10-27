<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Payment\Method;

use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class EntityPaymentMethodsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentTransactionProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentTransactionProvider;

    /**
     * @var EntityPaymentMethodsProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);

        $this->provider = new EntityPaymentMethodsProvider($this->paymentTransactionProvider);
    }

    public function testGetPaymentMethodsForExistingEntity()
    {
        $entity = new \stdClass();

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentMethods')
            ->with($entity)
            ->willReturn(['pm1']);

        $this->assertEquals(['pm1'], $this->provider->getPaymentMethods($entity));
    }

    public function testGetPaymentMethodsForExistingEntityWithStoredPaymentMethods()
    {
        $entity = new \stdClass();

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentMethods')
            ->with($entity)
            ->willReturn(['pm1']);

        $this->provider->storePaymentMethodsToEntity($entity, ['pm2']);
        $this->assertEquals(['pm1'], $this->provider->getPaymentMethods($entity));
    }

    public function testGetPaymentMethodsForNewEntityWithStoredPaymentMethods()
    {
        $entity = new \stdClass();

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentMethods')
            ->with($entity)
            ->willReturn([]);

        $this->provider->storePaymentMethodsToEntity($entity, ['pm2']);
        $this->assertEquals(['pm2'], $this->provider->getPaymentMethods($entity));
    }
}
