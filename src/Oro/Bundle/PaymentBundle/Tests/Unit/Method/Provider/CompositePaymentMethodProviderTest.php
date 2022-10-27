<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;

class CompositePaymentMethodProviderTest extends \PHPUnit\Framework\TestCase
{
    private const IDENTIFIER1 = 'test1';
    private const IDENTIFIER2 = 'test2';
    private const WRONG_IDENTIFIER = 'wrong';

    /** @var array */
    private $methods;

    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $firstProvider;

    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $secondProvider;

    /** @var CompositePaymentMethodProvider */
    private $compositeProvider;

    protected function setUp(): void
    {
        $this->methods = [
            self::IDENTIFIER1 => $this->getMethod(self::IDENTIFIER1),
            self::IDENTIFIER2 => $this->getMethod(self::IDENTIFIER2),
        ];

        $this->firstProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->secondProvider = $this->createMock(PaymentMethodProviderInterface::class);

        $this->compositeProvider = new CompositePaymentMethodProvider(
            [$this->firstProvider, $this->secondProvider]
        );
    }

    public function testGetPaymentMethods()
    {
        $this->firstProvider->expects(self::once())
            ->method('getPaymentMethods')
            ->willReturn([self::IDENTIFIER1 => $this->methods[self::IDENTIFIER1]]);
        $this->secondProvider->expects(self::once())
            ->method('getPaymentMethods')
            ->willReturn([self::IDENTIFIER2 => $this->methods[self::IDENTIFIER2]]);

        $methods = $this->compositeProvider->getPaymentMethods();
        self::assertCount(2, $methods);
        self::assertEquals($this->methods, $methods);
    }

    public function testGetPaymentMethod()
    {
        $this->mockHasPaymentMethodBehaviour();

        $this->firstProvider->expects(self::once())
            ->method('getPaymentMethod')
            ->with(self::IDENTIFIER1)
            ->willReturn($this->methods[self::IDENTIFIER1]);
        $this->secondProvider->expects(self::once())
            ->method('getPaymentMethod')
            ->with(self::IDENTIFIER2)
            ->willReturn($this->methods[self::IDENTIFIER2]);

        self::assertEquals(
            $this->methods[self::IDENTIFIER1],
            $this->compositeProvider->getPaymentMethod(self::IDENTIFIER1)
        );
        self::assertEquals(
            $this->methods[self::IDENTIFIER2],
            $this->compositeProvider->getPaymentMethod(self::IDENTIFIER2)
        );
    }

    public function testGetPaymentMethodExceptionTriggered()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/There is no payment method for "\w+" identifier/');#z
        $this->compositeProvider->getPaymentMethod(self::WRONG_IDENTIFIER);
    }

    public function testHasPaymentMethod()
    {
        $this->mockHasPaymentMethodBehaviour();

        self::assertEquals(true, $this->compositeProvider->hasPaymentMethod(self::IDENTIFIER1));
        self::assertEquals(true, $this->compositeProvider->hasPaymentMethod(self::IDENTIFIER2));
    }

    private function getMethod(string $identifier): PaymentMethodInterface
    {
        $method = $this->createMock(PaymentMethodInterface::class);
        $method->expects(self::any())
            ->method('getIdentifier')
            ->willReturn($identifier);

        return $method;
    }

    private function mockHasPaymentMethodBehaviour()
    {
        $this->firstProvider->expects(self::any())
            ->method('hasPaymentMethod')
            ->willReturnMap([
                [self::IDENTIFIER1, true],
                [self::IDENTIFIER2, false],
            ]);
        $this->secondProvider->expects(self::any())
            ->method('hasPaymentMethod')
            ->willReturnMap([
                [self::IDENTIFIER1, false],
                [self::IDENTIFIER2, true],
            ]);
    }
}
