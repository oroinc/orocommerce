<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\Provider\ExpressCheckoutMethodViewProvider;
use Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView;

class ExpressCheckoutMethodViewProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @internal */
    const IDENTIFIER1 = 'test1';

    /** @internal */
    const IDENTIFIER2 = 'test2';

    /** @internal */
    const WRONG_IDENTIFIER = 'wrong';

    /** @var PaymentConfigProviderInterface */
    private $configProvider;

    /** @var ExpressCheckoutMethodViewProvider */
    private $provider;

    /** @var array|PaymentConfigInterface[]|\PHPUnit_Framework_MockObject_MockObject[] */
    private $paymentConfigs;

    public function setUp()
    {
        $this->paymentConfigs = [
            $this->buildPaymentConfig(self::IDENTIFIER1),
            $this->buildPaymentConfig(self::IDENTIFIER2),
        ];

        $this->configProvider = $this->createMock(PaymentConfigProviderInterface::class);
        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn($this->paymentConfigs);

        $this->provider = new ExpressCheckoutMethodViewProvider($this->configProvider);
    }

    public function testHasPaymentMethodViewForCorrectIdentifier()
    {
        static::assertTrue($this->provider->hasPaymentMethodView(self::IDENTIFIER1));
    }

    public function testHasPaymentMethodViewForWrongIdentifier()
    {
        static::assertTrue($this->provider->hasPaymentMethodView(self::WRONG_IDENTIFIER));
    }

    public function testGetPaymentMethodViewReturnsCorrectObject()
    {
        $expectedView = $this->buildExpressCheckoutMethodView($this->paymentConfigs[0]);

        static::assertEquals(
            $expectedView,
            $this->provider->getPaymentMethodView(self::IDENTIFIER1)
        );
    }

    public function testGetPaymentMethodViewForWrongIdentifier()
    {
        static::assertNull($this->provider->getPaymentMethodView(self::WRONG_IDENTIFIER));
    }

    public function testGetPaymentMethodViewsReturnsCorrectObjects()
    {
        $expectedViews = [
            $this->buildExpressCheckoutMethodView($this->paymentConfigs[0]),
            $this->buildExpressCheckoutMethodView($this->paymentConfigs[1]),
        ];

        static::assertEquals(
            $expectedViews,
            $this->provider->getPaymentMethodViews([self::IDENTIFIER1, self::IDENTIFIER2])
        );
    }

    public function testGetPaymentMethodViewsForWrongIdentifier()
    {
        static::assertEmpty($this->provider->getPaymentMethodViews(self::WRONG_IDENTIFIER));
    }

    /**
     * @param string $identifier
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PaymentConfigInterface
     */
    private function buildPaymentConfig($identifier)
    {
        $config = $this->createMock(PaymentConfigInterface::class);
        $config->expects(static::any())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        return $config;
    }

    /**
     * @param PaymentConfigInterface $config
     *
     * @return PayPalExpressCheckoutPaymentMethodView
     */
    private function buildExpressCheckoutMethodView(PaymentConfigInterface $config)
    {
        return new PayPalExpressCheckoutPaymentMethodView($config);
    }
}
