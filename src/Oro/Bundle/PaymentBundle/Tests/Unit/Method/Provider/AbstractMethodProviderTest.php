<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;

abstract class AbstractMethodProviderTest extends \PHPUnit\Framework\TestCase
{
    const IDENTIFIER1 = 'test1';
    const IDENTIFIER2 = 'test2';
    const WRONG_IDENTIFIER = 'wrong';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $factory;

    /**
     * @var PaymentMethodProviderInterface
     */
    protected $methodProvider;

    /**
     * @var string
     */
    protected $paymentConfigClass;

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
        $this->factory->expects(static::once())
            ->method('create')
            ->with($config)
            ->willReturn($method);

        static::assertEquals($method, $this->methodProvider->getPaymentMethod(self::IDENTIFIER1));
    }

    /**
     * @param string  $identifier
     * @param boolean $expectedResult
     *
     * @dataProvider hasPaymentMethodDataProvider
     */
    public function testHasPaymentMethod($identifier, $expectedResult)
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $method = $this->createMock(PaymentMethodInterface::class);
        $this->factory->expects(static::once())
            ->method('create')
            ->with($config)
            ->willReturn($method);

        static::assertEquals($expectedResult, $this->methodProvider->hasPaymentMethod($identifier));
    }

    public function testGetPaymentMethodForWrongIdentifier()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $method = $this->createMock(PaymentMethodInterface::class);
        $this->factory->expects(static::once())
            ->method('create')
            ->with($config)
            ->willReturn($method);

        static::assertNull($this->methodProvider->getPaymentMethod(self::WRONG_IDENTIFIER));
    }

    public function testGetPaymentMethods()
    {
        $config1 = $this->buildPaymentConfig(self::IDENTIFIER1);
        $config2 = $this->buildPaymentConfig(self::IDENTIFIER2);

        $this->configProvider->expects(static::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config1, $config2]);

        $method1 = $this->createMock(PaymentMethodInterface::class);
        $this->factory->expects(static::at(0))
            ->method('create')
            ->with($config1)
            ->willReturn($method1);

        $method2 = $this->createMock(PaymentMethodInterface::class);
        $this->factory->expects(static::at(1))
            ->method('create')
            ->with($config2)
            ->willReturn($method2);

        static::assertEquals(
            [self::IDENTIFIER1 => $method1, self::IDENTIFIER2 => $method2],
            $this->methodProvider->getPaymentMethods()
        );
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
