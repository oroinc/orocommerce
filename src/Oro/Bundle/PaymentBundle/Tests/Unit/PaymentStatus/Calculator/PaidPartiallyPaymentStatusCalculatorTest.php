<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\PaymentStatus\Calculator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaidPartiallyPaymentStatusCalculator;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculationHelper;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PaidPartiallyPaymentStatusCalculatorTest extends TestCase
{
    private PaidPartiallyPaymentStatusCalculator $calculator;

    protected function setUp(): void
    {
        $helper = new PaymentStatusCalculationHelper();
        $this->calculator = new PaidPartiallyPaymentStatusCalculator($helper);
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

    public function testCalculatePaymentStatusReturnsPaidPartially(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$captureTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::PAID_PARTIALLY, $result);
    }

    public function testCalculatePaymentStatusReturnsNullIfFullyPaid(): void
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

    public function testCalculatePaymentStatusReturnsNullIfNoSuccessfulTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $failedTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$failedTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresAuthorizeTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $authorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$authorizeTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusWithChargeTransaction(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $chargeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CHARGE)
            ->setAmount(30.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$chargeTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::PAID_PARTIALLY, $result);
    }

    public function testCalculatePaymentStatusWithPurchaseTransaction(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $purchaseTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setAmount(40.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$purchaseTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::PAID_PARTIALLY, $result);
    }

    public function testCalculatePaymentStatusWithMultipleValidTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(20.0)
            ->setActive(true)
            ->setSuccessful(true);

        $chargeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CHARGE)
            ->setAmount(30.0)
            ->setActive(true)
            ->setSuccessful(true);

        $purchaseTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setAmount(20.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([
                $captureTransaction,
                $chargeTransaction,
                $purchaseTransaction,
            ]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::PAID_PARTIALLY, $result);
    }

    public function testCalculatePaymentStatusIgnoresInvalidActions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $authorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(30.0)
            ->setActive(true)
            ->setSuccessful(true);

        $invoiceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(20.0)
            ->setActive(true)
            ->setSuccessful(true);

        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(10.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([
                $authorizeTransaction,
                $invoiceTransaction,
                $captureTransaction,
            ]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::PAID_PARTIALLY, $result);
    }

    public function testCalculatePaymentStatusWithMixedSuccessfulAndFailedTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $successfulTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(30.0)
            ->setActive(true)
            ->setSuccessful(true);

        $failedTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$successfulTransaction, $failedTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::PAID_PARTIALLY, $result);
    }

    public function testCalculatePaymentStatusWithExcessivePayment(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(150.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$captureTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }
}
