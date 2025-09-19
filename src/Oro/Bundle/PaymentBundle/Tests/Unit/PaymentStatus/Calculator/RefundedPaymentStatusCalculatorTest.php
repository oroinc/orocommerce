<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\PaymentStatus\Calculator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculationHelper;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\RefundedPaymentStatusCalculator;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class RefundedPaymentStatusCalculatorTest extends TestCase
{
    private RefundedPaymentStatusCalculator $calculator;

    protected function setUp(): void
    {
        $helper = new PaymentStatusCalculationHelper();
        $this->calculator = new RefundedPaymentStatusCalculator($helper);
    }

    public function testCalculatePaymentStatusReturnsNullIfNoTotal(): void
    {
        $context = new PaymentStatusCalculationContext([
            // no 'total'
        ]);

        self::assertNull($this->calculator->calculatePaymentStatus(new \stdClass(), $context));
    }

    public function testCalculatePaymentStatusReturnsNullIfNoPaymentTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            // no 'paymentTransactions'
        ]);

        self::assertNull($this->calculator->calculatePaymentStatus(new \stdClass(), $context));
    }

    public function testCalculatePaymentStatusReturnsRefundedPartially(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $refundTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$refundTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::REFUNDED_PARTIALLY, $result);
    }

    public function testCalculatePaymentStatusReturnsRefunded(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $refundTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$refundTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::REFUNDED, $result);
    }

    public function testCalculatePaymentStatusReturnsRefundedIfExcessiveRefund(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $refundTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(150.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$refundTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::REFUNDED, $result);
    }

    public function testCalculatePaymentStatusReturnsNullIfNoRefundTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$captureTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresUnsuccessfulRefundTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $failedRefundTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$failedRefundTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresCloneRefundTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $sourceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::VALIDATE)
            ->setReference('ref123');

        $cloneRefundTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true)
            ->setReference('ref123')
            ->setSourcePaymentTransaction($sourceTransaction);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$cloneRefundTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusWithMultipleRefundTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $refundTransaction1 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(40.0)
            ->setActive(true)
            ->setSuccessful(true);

        $refundTransaction2 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(60.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$refundTransaction1, $refundTransaction2]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::REFUNDED, $result);
    }

    public function testCalculatePaymentStatusWithPartialMultipleRefundTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $refundTransaction1 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(30.0)
            ->setActive(true)
            ->setSuccessful(true);

        $refundTransaction2 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(40.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$refundTransaction1, $refundTransaction2]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::REFUNDED_PARTIALLY, $result);
    }

    public function testCalculatePaymentStatusWithMixedTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $refundTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(30.0)
            ->setActive(true)
            ->setSuccessful(true);

        $authorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([
                $captureTransaction,
                $refundTransaction,
                $authorizeTransaction,
            ]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::REFUNDED_PARTIALLY, $result);
    }

    public function testCalculatePaymentStatusWithMixedSuccessfulAndFailedRefunds(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $successfulRefundTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(60.0)
            ->setActive(true)
            ->setSuccessful(true);

        $failedRefundTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$successfulRefundTransaction, $failedRefundTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::REFUNDED_PARTIALLY, $result);
    }
}
