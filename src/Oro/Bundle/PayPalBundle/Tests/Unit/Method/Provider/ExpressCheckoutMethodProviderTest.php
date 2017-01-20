<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalExpressCheckoutConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\Factory\PayPalExpressCheckoutPaymentMethodFactoryInterface;
use Oro\Bundle\PayPalBundle\Method\Provider\ExpressCheckoutMethodProvider;

class ExpressCheckoutMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    const IDENTIFIER1 = 'test1';
    const IDENTIFIER2 = 'test2';
    const WRONG_IDENTIFIER = 'wrong';

    /**
     * @var PayPalExpressCheckoutConfigProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var PayPalExpressCheckoutPaymentMethodFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var ExpressCheckoutMethodProvider
     */
    protected $expressCheckoutMethodProvider;

    protected function setUp()
    {
        $this->configProvider = $this->createMock(PayPalExpressCheckoutConfigProviderInterface::class);
        $this->factory = $this->createMock(PayPalExpressCheckoutPaymentMethodFactoryInterface::class);
        $this->expressCheckoutMethodProvider = new ExpressCheckoutMethodProvider(
            $this->configProvider,
            $this->factory
        );
    }

    /**
     * @param string $identifier
     * @param boolean $expectedResult
     * @dataProvider hasPaymentMethodDataProvider
     */
    public function testHasPaymentMethod($identifier, $expectedResult)
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $method = $this->createMock(PaymentMethodInterface::class);
        $this->factory->expects($this->once())
            ->method('create')
            ->with($config)
            ->willReturn($method);

        static::assertEquals($expectedResult, $this->expressCheckoutMethodProvider->hasPaymentMethod($identifier));
    }

    /**
     * @return array
     */
    public function hasPaymentMethodDataProvider()
    {
        return [
            'existingIdentifier' => [
                'identifier' => self::IDENTIFIER1,
                'expectedResult' => true,
            ],
            'notExistingIdentifier' => [
                'identifier' => self::WRONG_IDENTIFIER,
                'expectedResult' => false,
            ],
        ];
    }

    public function testGetPaymentMethodForCorrectIdentifier()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $method = $this->createMock(PaymentMethodInterface::class);
        $this->factory->expects($this->once())
            ->method('create')
            ->with($config)
            ->willReturn($method);

        static::assertEquals(
            $method,
            $this->expressCheckoutMethodProvider->getPaymentMethod(self::IDENTIFIER1)
        );
    }

    public function testGetPaymentMethodForWrongIdentifier()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $method = $this->createMock(PaymentMethodInterface::class);
        $this->factory->expects($this->once())
            ->method('create')
            ->with($config)
            ->willReturn($method);

        static::assertNull($this->expressCheckoutMethodProvider->getPaymentMethod(self::WRONG_IDENTIFIER));
    }

    public function testGetPaymentMethods()
    {

        $config1 = $this->buildPaymentConfig(self::IDENTIFIER1);
        $config2 = $this->buildPaymentConfig(self::IDENTIFIER2);

        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config1, $config2]);

        $method1 = $this->createMock(PaymentMethodInterface::class);
        $this->factory->expects($this->at(0))
            ->method('create')
            ->with($config1)
            ->willReturn($method1);

        $method2 = $this->createMock(PaymentMethodInterface::class);
        $this->factory->expects($this->at(1))
            ->method('create')
            ->with($config2)
            ->willReturn($method2);

        static::assertEquals(
            [self::IDENTIFIER1 => $method1, self::IDENTIFIER2 => $method2],
            $this->expressCheckoutMethodProvider->getPaymentMethods()
        );
    }

    /**
     * @param string $identifier
     * @return \PHPUnit_Framework_MockObject_MockObject|PayPalExpressCheckoutConfigInterface
     */
    private function buildPaymentConfig($identifier)
    {
        $config = $this->createMock(PayPalExpressCheckoutConfigInterface::class);
        $config->expects(static::any())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        return $config;
    }
}
