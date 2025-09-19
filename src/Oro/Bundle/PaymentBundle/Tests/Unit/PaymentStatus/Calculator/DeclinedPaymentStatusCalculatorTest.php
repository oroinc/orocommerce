<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\PaymentStatus\Calculator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\DeclinedPaymentStatusCalculator;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculationHelper;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use PHPUnit\Framework\TestCase;

final class DeclinedPaymentStatusCalculatorTest extends TestCase
{
    private DeclinedPaymentStatusCalculator $calculator;

    protected function setUp(): void
    {
        $helper = new PaymentStatusCalculationHelper();
        $this->calculator = new DeclinedPaymentStatusCalculator($helper);
    }

    public function testCalculatePaymentStatusReturnsNullIfNoPaymentTransactions(): void
    {
        $context = new PaymentStatusCalculationContext([
            // no 'paymentTransactions'
        ]);

        self::assertNull($this->calculator->calculatePaymentStatus(new \stdClass(), $context));
    }

    public function testCalculatePaymentStatusReturnsNullIfEmptyPaymentTransactions(): void
    {
        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([]),
        ]);

        self::assertNull($this->calculator->calculatePaymentStatus(new \stdClass(), $context));
    }

    public function testCalculatePaymentStatusReturnsDeclined(): void
    {
        $failedTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(false)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$failedTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::DECLINED, $result);
    }

    public function testCalculatePaymentStatusReturnsNullIfSomeTransactionsSuccessful(): void
    {
        $failedTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(false)
            ->setSuccessful(false);

        $successfulTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$failedTransaction, $successfulTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusReturnsNullIfTransactionIsActive(): void
    {
        $activeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$activeTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusReturnsNullIfTransactionIsSuccessful(): void
    {
        $successfulTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(false)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$successfulTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusWithMultipleDeclinedTransactions(): void
    {
        $failedTransaction1 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(50.0)
            ->setActive(false)
            ->setSuccessful(false);

        $failedTransaction2 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(false)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$failedTransaction1, $failedTransaction2]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::DECLINED, $result);
    }

    public function testCalculatePaymentStatusWithMixedTransactionActions(): void
    {
        $failedCaptureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(50.0)
            ->setActive(false)
            ->setSuccessful(false);

        $failedAuthorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(false)
            ->setSuccessful(false);

        $failedChargeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CHARGE)
            ->setAmount(75.0)
            ->setActive(false)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([
                $failedCaptureTransaction,
                $failedAuthorizeTransaction,
                $failedChargeTransaction,
            ]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::DECLINED, $result);
    }

    public function testCalculatePaymentStatusIgnoresTransactionValues(): void
    {
        $failedTransactionWithZeroAmount = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(0.0)
            ->setActive(false)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$failedTransactionWithZeroAmount]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::DECLINED, $result);
    }
}
