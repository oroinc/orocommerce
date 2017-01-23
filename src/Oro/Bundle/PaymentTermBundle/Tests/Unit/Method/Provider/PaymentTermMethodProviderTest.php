<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;
use Oro\Bundle\PaymentTermBundle\Method\Factory\PaymentTermPaymentMethodFactoryInterface;
use Oro\Bundle\PaymentTermBundle\Method\Provider\PaymentTermMethodProvider;

class PaymentTermMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentTermPaymentMethodFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var PaymentTermConfigProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var PaymentTermConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentConfig;

    /**
     * @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $method;

    /**
     * @var PaymentTermMethodProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->paymentConfig = $this->createMock(PaymentTermConfigInterface::class);
        $this->paymentConfig->expects(static::any())
            ->method('getPaymentMethodIdentifier')
            ->willReturn('payment_term_1');

        $this->configProvider = $this->createMock(PaymentTermConfigProviderInterface::class);
        $this->configProvider->expects(static::any())->method('getPaymentConfigs')->willReturn([$this->paymentConfig]);

        $this->method = $this->createMock(PaymentMethodInterface::class);

        $this->factory = $this->createMock(PaymentTermPaymentMethodFactoryInterface::class);
        $this->factory->expects($this->once())
            ->method('create')
            ->with($this->paymentConfig)
            ->willReturn($this->method);

        $this->provider = new PaymentTermMethodProvider($this->configProvider, $this->factory);
    }

    public function testHasPaymentMethod()
    {
        static::assertTrue($this->provider->hasPaymentMethod('payment_term_1'));
        static::assertFalse($this->provider->hasPaymentMethod('not_existing'));
    }

    public function testGetPaymentMethods()
    {
        static::assertEquals(['payment_term_1' => $this->method], $this->provider->getPaymentMethods());
    }

    public function testGetPaymentMethod()
    {
        static::assertEquals($this->method, $this->provider->getPaymentMethod('payment_term_1'));
    }
}
