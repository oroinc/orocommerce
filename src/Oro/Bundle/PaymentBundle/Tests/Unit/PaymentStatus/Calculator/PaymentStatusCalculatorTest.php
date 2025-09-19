<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\PaymentStatus\Calculator;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculator;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculatorInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContextFactory;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PaymentStatusCalculatorTest extends TestCase
{
    private PaymentStatusCalculationContextFactory&MockObject $contextFactory;
    private PaymentStatusCalculator $calculator;

    protected function setUp(): void
    {
        $this->contextFactory = $this->createMock(PaymentStatusCalculationContextFactory::class);
    }

    public function testCalculatePaymentStatusWithFirstCalculatorReturningStatus(): void
    {
        $order = new Order();
        $context = new PaymentStatusCalculationContext([]);

        $calculator1 = $this->createMock(PaymentStatusCalculatorInterface::class);
        $calculator2 = $this->createMock(PaymentStatusCalculatorInterface::class);

        $calculator1
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($order, $context)
            ->willReturn(PaymentStatuses::PAID_IN_FULL);

        $calculator2
            ->expects(self::never())
            ->method('calculatePaymentStatus');

        $this->contextFactory
            ->expects(self::once())
            ->method('createPaymentStatusCalculationContext')
            ->with($order)
            ->willReturn($context);

        $this->calculator = new PaymentStatusCalculator(
            [$calculator1, $calculator2],
            $this->contextFactory
        );

        $result = $this->calculator->calculatePaymentStatus($order);

        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $result);
    }

    public function testCalculatePaymentStatusWithSecondCalculatorReturningStatus(): void
    {
        $order = new Order();
        $context = new PaymentStatusCalculationContext([]);

        $calculator1 = $this->createMock(PaymentStatusCalculatorInterface::class);
        $calculator2 = $this->createMock(PaymentStatusCalculatorInterface::class);

        $calculator1
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($order, $context)
            ->willReturn(null);

        $calculator2
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($order, $context)
            ->willReturn(PaymentStatuses::AUTHORIZED);

        $this->contextFactory
            ->expects(self::once())
            ->method('createPaymentStatusCalculationContext')
            ->with($order)
            ->willReturn($context);

        $this->calculator = new PaymentStatusCalculator(
            [$calculator1, $calculator2],
            $this->contextFactory
        );

        $result = $this->calculator->calculatePaymentStatus($order);

        self::assertEquals(PaymentStatuses::AUTHORIZED, $result);
    }

    public function testCalculatePaymentStatusWithNoCalculatorReturningStatus(): void
    {
        $order = new Order();
        $context = new PaymentStatusCalculationContext([]);

        $calculator1 = $this->createMock(PaymentStatusCalculatorInterface::class);
        $calculator2 = $this->createMock(PaymentStatusCalculatorInterface::class);

        $calculator1
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($order, $context)
            ->willReturn(null);

        $calculator2
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($order, $context)
            ->willReturn(null);

        $this->contextFactory
            ->expects(self::once())
            ->method('createPaymentStatusCalculationContext')
            ->with($order)
            ->willReturn($context);

        $this->calculator = new PaymentStatusCalculator(
            [$calculator1, $calculator2],
            $this->contextFactory
        );

        $result = $this->calculator->calculatePaymentStatus($order);

        self::assertEquals(PaymentStatuses::PENDING, $result);
    }

    public function testCalculatePaymentStatusWithEmptyCalculators(): void
    {
        $order = new Order();
        $context = new PaymentStatusCalculationContext([]);

        $this->contextFactory
            ->expects(self::once())
            ->method('createPaymentStatusCalculationContext')
            ->with($order)
            ->willReturn($context);

        $this->calculator = new PaymentStatusCalculator(
            [],
            $this->contextFactory
        );

        $result = $this->calculator->calculatePaymentStatus($order);

        self::assertEquals(PaymentStatuses::PENDING, $result);
    }

    public function testCalculatePaymentStatusWithProvidedContext(): void
    {
        $order = new Order();
        $providedContext = new PaymentStatusCalculationContext(['custom' => 'data']);

        $calculator1 = $this->createMock(PaymentStatusCalculatorInterface::class);

        $calculator1
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($order, $providedContext)
            ->willReturn(PaymentStatuses::DECLINED);

        $this->contextFactory
            ->expects(self::never())
            ->method('createPaymentStatusCalculationContext');

        $this->calculator = new PaymentStatusCalculator(
            [$calculator1],
            $this->contextFactory
        );

        $result = $this->calculator->calculatePaymentStatus($order, $providedContext);

        self::assertEquals(PaymentStatuses::DECLINED, $result);
    }

    public function testCalculatePaymentStatusWithMultipleCalculatorsReturningDifferentStatuses(): void
    {
        $order = new Order();
        $context = new PaymentStatusCalculationContext([]);

        $calculator1 = $this->createMock(PaymentStatusCalculatorInterface::class);
        $calculator2 = $this->createMock(PaymentStatusCalculatorInterface::class);
        $calculator3 = $this->createMock(PaymentStatusCalculatorInterface::class);

        $calculator1
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($order, $context)
            ->willReturn(null);

        $calculator2
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($order, $context)
            ->willReturn(PaymentStatuses::PAID_PARTIALLY);

        $calculator3
            ->expects(self::never())
            ->method('calculatePaymentStatus');

        $this->contextFactory
            ->expects(self::once())
            ->method('createPaymentStatusCalculationContext')
            ->with($order)
            ->willReturn($context);

        $this->calculator = new PaymentStatusCalculator(
            [$calculator1, $calculator2, $calculator3],
            $this->contextFactory
        );

        $result = $this->calculator->calculatePaymentStatus($order);

        self::assertEquals(PaymentStatuses::PAID_PARTIALLY, $result);
    }

    public function testCalculatePaymentStatusWithSingleCalculator(): void
    {
        $order = new Order();
        $context = new PaymentStatusCalculationContext([]);

        $calculator = $this->createMock(PaymentStatusCalculatorInterface::class);

        $calculator
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($order, $context)
            ->willReturn(PaymentStatuses::INVOICED);

        $this->contextFactory
            ->expects(self::once())
            ->method('createPaymentStatusCalculationContext')
            ->with($order)
            ->willReturn($context);

        $this->calculator = new PaymentStatusCalculator(
            [$calculator],
            $this->contextFactory
        );

        $result = $this->calculator->calculatePaymentStatus($order);

        self::assertEquals(PaymentStatuses::INVOICED, $result);
    }

    public function testCalculatePaymentStatusWithAllCalculatorsReturningNull(): void
    {
        $order = new Order();
        $context = new PaymentStatusCalculationContext([]);

        $calculator1 = $this->createMock(PaymentStatusCalculatorInterface::class);
        $calculator2 = $this->createMock(PaymentStatusCalculatorInterface::class);
        $calculator3 = $this->createMock(PaymentStatusCalculatorInterface::class);

        $calculator1
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($order, $context)
            ->willReturn(null);

        $calculator2
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($order, $context)
            ->willReturn(null);

        $calculator3
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($order, $context)
            ->willReturn(null);

        $this->contextFactory
            ->expects(self::once())
            ->method('createPaymentStatusCalculationContext')
            ->with($order)
            ->willReturn($context);

        $this->calculator = new PaymentStatusCalculator(
            [$calculator1, $calculator2, $calculator3],
            $this->contextFactory
        );

        $result = $this->calculator->calculatePaymentStatus($order);

        self::assertEquals(PaymentStatuses::PENDING, $result);
    }
}
