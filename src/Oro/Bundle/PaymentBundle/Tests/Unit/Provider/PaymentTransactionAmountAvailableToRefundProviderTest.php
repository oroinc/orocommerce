<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculationHelper;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionAmountAvailableToRefundProvider;
use Oro\Component\Math\BigDecimal;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PaymentTransactionAmountAvailableToRefundProviderTest extends TestCase
{
    private PaymentTransactionRepository&MockObject $transactionRepository;
    private PaymentStatusCalculationHelper&MockObject $paymentStatusCalculationHelper;
    private RoundingServiceInterface&MockObject $roundingService;
    private PaymentTransactionAmountAvailableToRefundProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->transactionRepository = $this->createMock(PaymentTransactionRepository::class);
        $this->paymentStatusCalculationHelper = $this->createMock(PaymentStatusCalculationHelper::class);
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);

        $this->provider = new PaymentTransactionAmountAvailableToRefundProvider(
            $this->transactionRepository,
            $this->paymentStatusCalculationHelper,
            $this->roundingService
        );
    }

    public function testGetAvailableAmountToRefundWithNoRefundTransactions(): void
    {
        $sourceTransaction = (new PaymentTransaction())->setAmount(100.00);

        $this->transactionRepository
            ->expects(self::once())
            ->method('findSuccessfulRelatedTransactionsByAction')
            ->with($sourceTransaction, PaymentMethodInterface::REFUND)
            ->willReturn([]);

        $bigDecimalAmount = BigDecimal::of(0);
        $this->paymentStatusCalculationHelper
            ->expects(self::once())
            ->method('sumTransactionAmounts')
            ->with([])
            ->willReturn($bigDecimalAmount);

        $this->roundingService
            ->expects(self::once())
            ->method('round')
            ->with(100.0)
            ->willReturn(100.0);

        $result = $this->provider->getAvailableAmountToRefund($sourceTransaction);

        self::assertEquals(100.0, $result);
    }

    public function testGetAvailableAmountToRefundWithSingleRefundTransaction(): void
    {
        $sourceTransaction = (new PaymentTransaction())->setAmount(100.00);
        $refundTransaction = (new PaymentTransaction())
            ->setAmount(50.555555)
            ->setAction(PaymentMethodInterface::REFUND)
            ->setSuccessful(true);

        $this->transactionRepository
            ->expects(self::once())
            ->method('findSuccessfulRelatedTransactionsByAction')
            ->with($sourceTransaction, PaymentMethodInterface::REFUND)
            ->willReturn([$refundTransaction]);

        $bigDecimalAmount = BigDecimal::of('50.555555');
        $this->paymentStatusCalculationHelper
            ->expects(self::once())
            ->method('sumTransactionAmounts')
            ->with([$refundTransaction])
            ->willReturn($bigDecimalAmount);

        $this->roundingService
            ->expects(self::once())
            ->method('round')
            ->with(49.444445)
            ->willReturn(49.44);

        $result = $this->provider->getAvailableAmountToRefund($sourceTransaction);

        self::assertEquals(49.44, $result);
    }

    public function testGetAvailableAmountToRefundWithMultipleRefundTransactions(): void
    {
        $sourceTransaction = (new PaymentTransaction())->setAmount(131.50);
        $refundTransaction1 = (new PaymentTransaction())
            ->setAmount(30.00)
            ->setAction(PaymentMethodInterface::REFUND)
            ->setSuccessful(true);
        $refundTransaction2 = (new PaymentTransaction())
            ->setAmount(20.50)
            ->setAction(PaymentMethodInterface::REFUND)
            ->setSuccessful(true);
        $refundTransaction3 = (new PaymentTransaction())
            ->setAmount(15.255555)
            ->setAction(PaymentMethodInterface::REFUND)
            ->setSuccessful(true);

        $refundTransactions = [$refundTransaction1, $refundTransaction2, $refundTransaction3];

        $this->transactionRepository
            ->expects(self::once())
            ->method('findSuccessfulRelatedTransactionsByAction')
            ->with($sourceTransaction, PaymentMethodInterface::REFUND)
            ->willReturn($refundTransactions);

        $bigDecimalAmount = BigDecimal::of('65.755555');
        $this->paymentStatusCalculationHelper
            ->expects(self::once())
            ->method('sumTransactionAmounts')
            ->with($refundTransactions)
            ->willReturn($bigDecimalAmount);

        $this->roundingService
            ->expects(self::once())
            ->method('round')
            ->with(65.744445)
            ->willReturn(65.74);

        $result = $this->provider->getAvailableAmountToRefund($sourceTransaction);

        self::assertEquals(65.74, $result);
    }

    public function testGetAvailableAmountToRefundWhenFullyRefunded(): void
    {
        $sourceTransaction = (new PaymentTransaction())->setAmount(100.00);
        $refundTransaction = (new PaymentTransaction())
            ->setAmount(100.00)
            ->setAction(PaymentMethodInterface::REFUND)
            ->setSuccessful(true);

        $this->transactionRepository
            ->expects(self::once())
            ->method('findSuccessfulRelatedTransactionsByAction')
            ->with($sourceTransaction, PaymentMethodInterface::REFUND)
            ->willReturn([$refundTransaction]);

        $bigDecimalAmount = BigDecimal::of('100.00');
        $this->paymentStatusCalculationHelper
            ->expects(self::once())
            ->method('sumTransactionAmounts')
            ->with([$refundTransaction])
            ->willReturn($bigDecimalAmount);

        $this->roundingService
            ->expects(self::once())
            ->method('round')
            ->with(0.0)
            ->willReturn(0.0);

        $result = $this->provider->getAvailableAmountToRefund($sourceTransaction);

        self::assertEquals(0.0, $result);
    }

    public function testGetAvailableAmountToRefundWhenRefundsExceedSourceAmount(): void
    {
        $sourceTransaction = (new PaymentTransaction())->setAmount(100.00);
        $refundTransaction = (new PaymentTransaction())
            ->setAmount(120.00)
            ->setAction(PaymentMethodInterface::REFUND)
            ->setSuccessful(true);

        $this->transactionRepository
            ->expects(self::once())
            ->method('findSuccessfulRelatedTransactionsByAction')
            ->with($sourceTransaction, PaymentMethodInterface::REFUND)
            ->willReturn([$refundTransaction]);

        $bigDecimalAmount = BigDecimal::of('120.00');
        $this->paymentStatusCalculationHelper
            ->expects(self::once())
            ->method('sumTransactionAmounts')
            ->with([$refundTransaction])
            ->willReturn($bigDecimalAmount);

        $this->roundingService
            ->expects(self::once())
            ->method('round')
            ->with(0.0)
            ->willReturn(0.0);

        $result = $this->provider->getAvailableAmountToRefund($sourceTransaction);

        self::assertEquals(0.0, $result);
    }
}
