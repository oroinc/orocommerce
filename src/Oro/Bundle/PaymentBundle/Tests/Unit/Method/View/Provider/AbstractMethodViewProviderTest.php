<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;

abstract class AbstractMethodViewProviderTest extends \PHPUnit\Framework\TestCase
{
    private const IDENTIFIER1 = 'test1';
    private const IDENTIFIER2 = 'test2';
    private const WRONG_IDENTIFIER = 'wrong';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $factory;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    /** @var PaymentMethodViewProviderInterface */
    protected $provider;

    /** @var string */
    protected $paymentConfigClass;

    public function testHasPaymentMethodViewForCorrectIdentifier()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(self::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $this->factory->expects(self::once())
            ->method('create')
            ->with($config)
            ->willReturn($view);

        self::assertTrue($this->provider->hasPaymentMethodView(self::IDENTIFIER1));
    }

    public function testHasPaymentMethodViewForWrongIdentifier()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(self::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $this->factory->expects(self::once())
            ->method('create')
            ->with($config)
            ->willReturn($view);

        self::assertFalse($this->provider->hasPaymentMethodView(self::WRONG_IDENTIFIER));
    }

    public function testGetPaymentMethodViewReturnsCorrectObject()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(self::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $this->factory->expects(self::once())
            ->method('create')
            ->with($config)
            ->willReturn($view);

        self::assertEquals(
            $view,
            $this->provider->getPaymentMethodView(self::IDENTIFIER1)
        );
    }

    public function testGetPaymentMethodViewForWrongIdentifier()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(self::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $this->factory->expects(self::once())
            ->method('create')
            ->with($config)
            ->willReturn($view);

        self::assertNull($this->provider->getPaymentMethodView(self::WRONG_IDENTIFIER));
    }

    public function testGetPaymentMethodViewsReturnsCorrectObjects()
    {
        $config1 = $this->buildPaymentConfig(self::IDENTIFIER1);
        $config2 = $this->buildPaymentConfig(self::IDENTIFIER2);

        $this->configProvider->expects(self::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config1, $config2]);

        $view1 = $this->createMock(PaymentMethodViewInterface::class);
        $view2 = $this->createMock(PaymentMethodViewInterface::class);
        $this->factory->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive([$config1], [$config2])
            ->willReturnOnConsecutiveCalls($view1, $view2);

        self::assertEquals(
            [$view1, $view2],
            $this->provider->getPaymentMethodViews([self::IDENTIFIER1, self::IDENTIFIER2])
        );
    }

    public function testGetPaymentMethodViewsForWrongIdentifier()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(self::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $this->factory->expects(self::once())
            ->method('create')
            ->with($config)
            ->willReturn($view);

        self::assertEmpty($this->provider->getPaymentMethodViews([self::WRONG_IDENTIFIER]));
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function buildPaymentConfig(string $identifier)
    {
        $config = $this->createMock($this->paymentConfigClass);
        $config->expects(self::any())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        return $config;
    }
}
