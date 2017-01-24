<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;
use Oro\Bundle\PaymentTermBundle\Method\View\Factory\PaymentTermPaymentMethodViewFactoryInterface;
use Oro\Bundle\PaymentTermBundle\Method\View\Provider\PaymentTermMethodViewProvider;

class PaymentTermMethodViewProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @internal */
    const IDENTIFIER1 = 'test1';

    /** @internal */
    const IDENTIFIER2 = 'test2';

    /** @internal */
    const WRONG_IDENTIFIER = 'wrong';

    /**
     * @var PaymentTermPaymentMethodViewFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var PaymentTermConfigProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var PaymentTermMethodViewProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->factory = $this->createMock(PaymentTermPaymentMethodViewFactoryInterface::class);

        $this->configProvider = $this->createMock(PaymentTermConfigProviderInterface::class);

        $this->provider = new PaymentTermMethodViewProvider(
            $this->factory,
            $this->configProvider
        );
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
     * @return \PHPUnit_Framework_MockObject_MockObject|PaymentTermConfigInterface
     */
    private function buildPaymentConfig($identifier)
    {
        $config = $this->createMock(PaymentTermConfigInterface::class);
        $config->expects(static::any())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        return $config;
    }
}
