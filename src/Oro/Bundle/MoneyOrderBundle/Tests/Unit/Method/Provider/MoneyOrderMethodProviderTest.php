<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProviderInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Factory\MoneyOrderPaymentMethodFactoryInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Provider\MoneyOrderMethodProvider;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class MoneyOrderMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    const IDENTIFIER = 'money_order_1';

    /**
     * @var MoneyOrderPaymentMethodFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var MoneyOrderConfigProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var MoneyOrderConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentConfig;

    /**
     * @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $method;

    /**
     * @var MoneyOrderMethodProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->paymentConfig = $this->createMock(MoneyOrderConfigInterface::class);
        $this->paymentConfig->expects(static::any())
            ->method('getPaymentMethodIdentifier')
            ->willReturn(static::IDENTIFIER);

        $this->configProvider = $this->createMock(MoneyOrderConfigProviderInterface::class);
        $this->configProvider->expects(static::any())->method('getPaymentConfigs')->willReturn([$this->paymentConfig]);

        $this->method = $this->createMock(PaymentMethodInterface::class);

        $this->factory = $this->createMock(MoneyOrderPaymentMethodFactoryInterface::class);
        $this->factory->expects(static::once())
            ->method('create')
            ->with($this->paymentConfig)
            ->willReturn($this->method);

        $this->provider = new MoneyOrderMethodProvider($this->configProvider, $this->factory);
    }

    public function testHasPaymentMethod()
    {
        static::assertTrue($this->provider->hasPaymentMethod(static::IDENTIFIER));
        static::assertFalse($this->provider->hasPaymentMethod('not_existing'));
    }

    public function testGetPaymentMethods()
    {
        static::assertEquals([static::IDENTIFIER => $this->method], $this->provider->getPaymentMethods());
    }

    public function testGetPaymentMethod()
    {
        static::assertEquals($this->method, $this->provider->getPaymentMethod(static::IDENTIFIER));
        static::assertNull($this->provider->getPaymentMethod('not_existing'));
    }
}
