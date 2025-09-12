<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\PaymentStatus\Calculator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaidInFullPaymentStatusCalculator;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculationHelper;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PaidInFullPaymentStatusCalculatorTest extends TestCase
{
    private PaidInFullPaymentStatusCalculator $calculator;

    protected function setUp(): void
    {
        $helper = new PaymentStatusCalculationHelper();
        $this->calculator = new PaidInFullPaymentStatusCalculator($helper);
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

    public function testCalculatePaymentStatusReturnsPaidInFull(): void
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
        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $result);
    }

    public function testCalculatePaymentStatusReturnsNullIfNotPaidInFull(): void
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
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusReturnsNullIfNoSuccessfulTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $failedTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
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
            ->setAmount(100.0)
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
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$chargeTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $result);
    }

    public function testCalculatePaymentStatusWithPurchaseTransaction(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $purchaseTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$purchaseTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $result);
    }

    public function testCalculatePaymentStatusWithMultipleTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $captureTransaction1 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(40.0)
            ->setActive(true)
            ->setSuccessful(true);

        $captureTransaction2 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(60.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$captureTransaction1, $captureTransaction2]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $result);
    }

    public function testCalculatePaymentStatusWithMixedValidTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(30.0)
            ->setActive(true)
            ->setSuccessful(true);

        $chargeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CHARGE)
            ->setAmount(40.0)
            ->setActive(true)
            ->setSuccessful(true);

        $purchaseTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setAmount(30.0)
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
        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $result);
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
        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $result);
    }

    public function testCalculatePaymentStatusIgnoresInvalidActions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $authorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(60.0)
            ->setActive(true)
            ->setSuccessful(true);

        $invoiceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(40.0)
            ->setActive(true)
            ->setSuccessful(true);

        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(50.0)
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
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusWithMixedSuccessfulAndFailedTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $successfulTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $failedTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => new ArrayCollection([$failedTransaction, $successfulTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $result);
    }
}
