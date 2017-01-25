<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\View\Provider;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProviderInterface;
use Oro\Bundle\MoneyOrderBundle\Method\View\Factory\MoneyOrderPaymentMethodViewFactoryInterface;
use Oro\Bundle\MoneyOrderBundle\Method\View\Provider\MoneyOrderMethodViewProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class MoneyOrderMethodViewProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @internal */
    const IDENTIFIER1 = 'test1';

    /** @internal */
    const IDENTIFIER2 = 'test2';

    /** @internal */
    const WRONG_IDENTIFIER = 'wrong';

    /** @var MoneyOrderPaymentMethodViewFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $factory;

    /** @var MoneyOrderConfigProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $configProvider;

    /** @var MoneyOrderMethodViewProvider */
    private $provider;

    public function setUp()
    {
        $this->factory = $this->createMock(MoneyOrderPaymentMethodViewFactoryInterface::class);
        $this->configProvider = $this->createMock(MoneyOrderConfigProviderInterface::class);
        $this->provider = new MoneyOrderMethodViewProvider($this->configProvider, $this->factory);
    }

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
     * @return \PHPUnit_Framework_MockObject_MockObject|MoneyOrderConfigInterface
     */
    private function buildPaymentConfig($identifier)
    {
        $config = $this->createMock(MoneyOrderConfigInterface::class);
        $config->expects(static::any())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        return $config;
    }
}
