<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;

abstract class AbstractMethodViewProviderTest extends \PHPUnit\Framework\TestCase
{
    const IDENTIFIER1 = 'test1';
    const IDENTIFIER2 = 'test2';
    const WRONG_IDENTIFIER = 'wrong';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $factory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $configProvider;

    /**
     * @var PaymentMethodViewProviderInterface
     */
    protected $provider;

    /**
     * @var string
     */
    protected $paymentConfigClass;

    public function testHasPaymentMethodViewForCorrectIdentifier()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $this->factory->expects(static::once())
            ->method('create')
            ->with($config)
            ->willReturn($view);

        static::assertTrue($this->provider->hasPaymentMethodView(self::IDENTIFIER1));
    }

    public function testHasPaymentMethodViewForWrongIdentifier()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $this->factory->expects(static::once())
            ->method('create')
            ->with($config)
            ->willReturn($view);

        static::assertFalse($this->provider->hasPaymentMethodView(self::WRONG_IDENTIFIER));
    }

    public function testGetPaymentMethodViewReturnsCorrectObject()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $this->factory->expects(static::once())
            ->method('create')
            ->with($config)
            ->willReturn($view);

        static::assertEquals(
            $view,
            $this->provider->getPaymentMethodView(self::IDENTIFIER1)
        );
    }

    public function testGetPaymentMethodViewForWrongIdentifier()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $this->factory->expects(static::once())
            ->method('create')
            ->with($config)
            ->willReturn($view);

        static::assertNull($this->provider->getPaymentMethodView(self::WRONG_IDENTIFIER));
    }

    public function testGetPaymentMethodViewsReturnsCorrectObjects()
    {
        $config1 = $this->buildPaymentConfig(self::IDENTIFIER1);
        $config2 = $this->buildPaymentConfig(self::IDENTIFIER2);

        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config1, $config2]);

        $view1 = $this->createMock(PaymentMethodViewInterface::class);
        $this->factory->expects(static::at(0))
            ->method('create')
            ->with($config1)
            ->willReturn($view1);

        $view2 = $this->createMock(PaymentMethodViewInterface::class);
        $this->factory->expects(static::at(1))
            ->method('create')
            ->with($config2)
            ->willReturn($view2);

        static::assertEquals(
            [$view1, $view2],
            $this->provider->getPaymentMethodViews([self::IDENTIFIER1, self::IDENTIFIER2])
        );
    }

    public function testGetPaymentMethodViewsForWrongIdentifier()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $this->factory->expects(static::once())
            ->method('create')
            ->with($config)
            ->willReturn($view);

        static::assertEmpty($this->provider->getPaymentMethodViews([self::WRONG_IDENTIFIER]));
    }

    /**
     * @param string $identifier
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function buildPaymentConfig($identifier)
    {
        $config = $this->createMock($this->paymentConfigClass);
        $config->expects(static::any())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        return $config;
    }
}
