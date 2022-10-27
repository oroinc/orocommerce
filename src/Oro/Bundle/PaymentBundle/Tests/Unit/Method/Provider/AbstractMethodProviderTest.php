<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;

abstract class AbstractMethodProviderTest extends \PHPUnit\Framework\TestCase
{
    private const IDENTIFIER1 = 'test1';
    private const IDENTIFIER2 = 'test2';
    private const WRONG_IDENTIFIER = 'wrong';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $factory;

    /** @var PaymentMethodProviderInterface */
    protected $methodProvider;

    /** @var string */
    protected $paymentConfigClass;

    public function hasPaymentMethodDataProvider(): array
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

        $this->configProvider->expects(self::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $method = $this->createMock(PaymentMethodInterface::class);
        $this->factory->expects(self::once())
            ->method('create')
            ->with($config)
            ->willReturn($method);

        self::assertEquals($method, $this->methodProvider->getPaymentMethod(self::IDENTIFIER1));
    }

    /**
     * @dataProvider hasPaymentMethodDataProvider
     */
    public function testHasPaymentMethod(string $identifier, bool $expectedResult)
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(self::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $method = $this->createMock(PaymentMethodInterface::class);
        $this->factory->expects(self::once())
            ->method('create')
            ->with($config)
            ->willReturn($method);

        self::assertEquals($expectedResult, $this->methodProvider->hasPaymentMethod($identifier));
    }

    public function testGetPaymentMethodForWrongIdentifier()
    {
        $config = $this->buildPaymentConfig(self::IDENTIFIER1);

        $this->configProvider->expects(self::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config]);

        $method = $this->createMock(PaymentMethodInterface::class);
        $this->factory->expects(self::once())
            ->method('create')
            ->with($config)
            ->willReturn($method);

        self::assertNull($this->methodProvider->getPaymentMethod(self::WRONG_IDENTIFIER));
    }

    public function testGetPaymentMethods()
    {
        $config1 = $this->buildPaymentConfig(self::IDENTIFIER1);
        $config2 = $this->buildPaymentConfig(self::IDENTIFIER2);

        $this->configProvider->expects(self::once())
            ->method('getPaymentConfigs')
            ->willReturn([$config1, $config2]);

        $method1 = $this->createMock(PaymentMethodInterface::class);
        $method2 = $this->createMock(PaymentMethodInterface::class);
        $this->factory->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive([$config1], [$config2])
            ->willReturnOnConsecutiveCalls($method1, $method2);

        self::assertEquals(
            [self::IDENTIFIER1 => $method1, self::IDENTIFIER2 => $method2],
            $this->methodProvider->getPaymentMethods()
        );
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
