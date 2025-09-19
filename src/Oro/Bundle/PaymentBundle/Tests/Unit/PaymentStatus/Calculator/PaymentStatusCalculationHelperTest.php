<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\PaymentStatus\Calculator;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculationHelper;
use Oro\Component\Math\BigDecimal;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PaymentStatusCalculationHelperTest extends TestCase
{
    private PaymentStatusCalculationHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new PaymentStatusCalculationHelper();
    }

    public function testSumTransactionAmountsWithEmptyArray(): void
    {
        $result = $this->helper->sumTransactionAmounts([]);

        self::assertTrue($result->isEqualTo(BigDecimal::of(0)));
    }

    public function testSumTransactionAmountsWithSingleTransaction(): void
    {
        $transaction = (new PaymentTransaction())
            ->setAmount(100.50);

        $result = $this->helper->sumTransactionAmounts([$transaction]);

        self::assertTrue($result->isEqualTo(BigDecimal::of('100.50')));
    }

    public function testSumTransactionAmountsWithMultipleTransactions(): void
    {
        $transaction1 = (new PaymentTransaction())
            ->setAmount(100.50);

        $transaction2 = (new PaymentTransaction())
            ->setAmount(25.25);

        $transaction3 = (new PaymentTransaction())
            ->setAmount(74.25);

        $result = $this->helper->sumTransactionAmounts([$transaction1, $transaction2, $transaction3]);

        self::assertTrue($result->isEqualTo(BigDecimal::of('200.00')));
    }

    public function testSumTransactionAmountsWithZeroAmounts(): void
    {
        $transaction1 = (new PaymentTransaction())
            ->setAmount(0.00);

        $transaction2 = (new PaymentTransaction())
            ->setAmount(50.00);

        $transaction3 = (new PaymentTransaction())
            ->setAmount(0.00);

        $result = $this->helper->sumTransactionAmounts([$transaction1, $transaction2, $transaction3]);

        self::assertTrue($result->isEqualTo(BigDecimal::of('50.00')));
    }

    public function testSumTransactionAmountsWithIterator(): void
    {
        $transaction1 = (new PaymentTransaction())
            ->setAmount(30.00);

        $transaction2 = (new PaymentTransaction())
            ->setAmount(20.00);

        $iterator = new \ArrayIterator([$transaction1, $transaction2]);

        $result = $this->helper->sumTransactionAmounts($iterator);

        self::assertTrue($result->isEqualTo(BigDecimal::of('50.00')));
    }

    public function testIsTransactionsAmountLessThanWithEmptyArray(): void
    {
        $result = $this->helper->isTransactionsAmountLessThan([], 100.0);

        self::assertFalse($result);
    }

    public function testIsTransactionsAmountLessThanWithEmptyIterator(): void
    {
        $iterator = new \ArrayIterator([]);

        $result = $this->helper->isTransactionsAmountLessThan($iterator, 100.0);

        self::assertFalse($result);
    }

    public function testIsTransactionsAmountLessThanReturnsTrueWhenLess(): void
    {
        $transaction1 = (new PaymentTransaction())
            ->setAmount(30.00);

        $transaction2 = (new PaymentTransaction())
            ->setAmount(20.00);

        $result = $this->helper->isTransactionsAmountLessThan([$transaction1, $transaction2], 100.0);

        self::assertTrue($result);
    }

    public function testIsTransactionsAmountLessThanReturnsFalseWhenEqual(): void
    {
        $transaction1 = (new PaymentTransaction())
            ->setAmount(50.00);

        $transaction2 = (new PaymentTransaction())
            ->setAmount(50.00);

        $result = $this->helper->isTransactionsAmountLessThan([$transaction1, $transaction2], 100.0);

        self::assertFalse($result);
    }

    public function testIsTransactionsAmountLessThanReturnsFalseWhenGreater(): void
    {
        $transaction1 = (new PaymentTransaction())
            ->setAmount(60.00);

        $transaction2 = (new PaymentTransaction())
            ->setAmount(50.00);

        $result = $this->helper->isTransactionsAmountLessThan([$transaction1, $transaction2], 100.0);

        self::assertFalse($result);
    }

    public function testIsTransactionsAmountGreaterThanOrEqualWithEmptyArray(): void
    {
        $result = $this->helper->isTransactionsAmountGreaterThanOrEqual([], 100.0);

        self::assertFalse($result);
    }

    public function testIsTransactionsAmountGreaterThanOrEqualWithEmptyIterator(): void
    {
        $iterator = new \ArrayIterator([]);

        $result = $this->helper->isTransactionsAmountGreaterThanOrEqual($iterator, 100.0);

        self::assertFalse($result);
    }

    public function testIsTransactionsAmountGreaterThanOrEqualReturnsTrueWhenEqual(): void
    {
        $transaction1 = (new PaymentTransaction())
            ->setAmount(50.00);

        $transaction2 = (new PaymentTransaction())
            ->setAmount(50.00);

        $result = $this->helper->isTransactionsAmountGreaterThanOrEqual([$transaction1, $transaction2], 100.0);

        self::assertTrue($result);
    }

    public function testIsTransactionsAmountGreaterThanOrEqualReturnsTrueWhenGreater(): void
    {
        $transaction1 = (new PaymentTransaction())
            ->setAmount(60.00);

        $transaction2 = (new PaymentTransaction())
            ->setAmount(50.00);

        $result = $this->helper->isTransactionsAmountGreaterThanOrEqual([$transaction1, $transaction2], 100.0);

        self::assertTrue($result);
    }

    public function testIsTransactionsAmountGreaterThanOrEqualReturnsFalseWhenLess(): void
    {
        $transaction1 = (new PaymentTransaction())
            ->setAmount(30.00);

        $transaction2 = (new PaymentTransaction())
            ->setAmount(20.00);

        $result = $this->helper->isTransactionsAmountGreaterThanOrEqual([$transaction1, $transaction2], 100.0);

        self::assertFalse($result);
    }

    public function testSumTransactionAmountsWithDecimalPrecision(): void
    {
        $transaction1 = (new PaymentTransaction())
            ->setAmount(10.33);

        $transaction2 = (new PaymentTransaction())
            ->setAmount(20.67);

        $transaction3 = (new PaymentTransaction())
            ->setAmount(5.50);

        $result = $this->helper->sumTransactionAmounts([$transaction1, $transaction2, $transaction3]);

        self::assertTrue($result->isEqualTo(BigDecimal::of('36.50')));
    }

    public function testIsTransactionsAmountLessThanWithIterator(): void
    {
        $transaction1 = (new PaymentTransaction())
            ->setAmount(25.00);

        $transaction2 = (new PaymentTransaction())
            ->setAmount(35.00);

        $iterator = new \ArrayIterator([$transaction1, $transaction2]);

        $result = $this->helper->isTransactionsAmountLessThan($iterator, 100.0);

        self::assertTrue($result);
    }

    public function testIsTransactionsAmountGreaterThanOrEqualWithIterator(): void
    {
        $transaction1 = (new PaymentTransaction())
            ->setAmount(55.00);

        $transaction2 = (new PaymentTransaction())
            ->setAmount(45.00);

        $iterator = new \ArrayIterator([$transaction1, $transaction2]);

        $result = $this->helper->isTransactionsAmountGreaterThanOrEqual($iterator, 100.0);

        self::assertTrue($result);
    }

    public function testSumTransactionAmountsWithDifferentTransactionTypes(): void
    {
        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(50.00);

        $refundTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(25.00);

        $authorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(75.00);

        $result = $this->helper->sumTransactionAmounts([
            $captureTransaction,
            $refundTransaction,
            $authorizeTransaction,
        ]);

        self::assertTrue($result->isEqualTo(BigDecimal::of('150.00')));
    }

    public function testAmountComparisonWithFloatingPointPrecision(): void
    {
        $transaction1 = (new PaymentTransaction())
            ->setAmount(33.33);

        $transaction2 = (new PaymentTransaction())
            ->setAmount(33.33);

        $transaction3 = (new PaymentTransaction())
            ->setAmount(33.34);

        $result = $this->helper->isTransactionsAmountGreaterThanOrEqual(
            [$transaction1, $transaction2, $transaction3],
            100.0
        );

        self::assertTrue($result);
    }
}
