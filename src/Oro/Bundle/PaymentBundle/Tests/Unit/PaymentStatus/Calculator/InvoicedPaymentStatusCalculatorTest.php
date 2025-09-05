<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\PaymentStatus\Calculator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\InvoicedPaymentStatusCalculator;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculationHelper;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class InvoicedPaymentStatusCalculatorTest extends TestCase
{
    private InvoicedPaymentStatusCalculator $calculator;

    protected function setUp(): void
    {
        $helper = new PaymentStatusCalculationHelper();
        $this->calculator = new InvoicedPaymentStatusCalculator($helper);
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

    public function testCalculatePaymentStatusReturnsInvoiced(): void
    {
        $invoiceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$invoiceTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::INVOICED, $result);
    }

    public function testCalculatePaymentStatusReturnsNullIfNoInvoiceTransactions(): void
    {
        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$captureTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusReturnsNullIfInvoiceTransactionNotActive(): void
    {
        $inactiveInvoiceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(100.0)
            ->setActive(false)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$inactiveInvoiceTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusReturnsNullIfInvoiceTransactionNotSuccessful(): void
    {
        $unsuccessfulInvoiceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$unsuccessfulInvoiceTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresCloneInvoiceTransactions(): void
    {
        $sourceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::VALIDATE)
            ->setReference('ref123');

        $cloneInvoiceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true)
            ->setReference('ref123')
            ->setSourcePaymentTransaction($sourceTransaction);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$cloneInvoiceTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusWithMultipleTransactionsContainingInvoice(): void
    {
        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(true);

        $invoiceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $authorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(75.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([
                $captureTransaction,
                $invoiceTransaction,
                $authorizeTransaction,
            ]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::INVOICED, $result);
    }

    public function testCalculatePaymentStatusWithMultipleInvoiceTransactions(): void
    {
        $invoiceTransaction1 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(true);

        $invoiceTransaction2 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(60.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$invoiceTransaction1, $invoiceTransaction2]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::INVOICED, $result);
    }

    public function testCalculatePaymentStatusWithMixedInvoiceTransactions(): void
    {
        $successfulInvoiceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $failedInvoiceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$failedInvoiceTransaction, $successfulInvoiceTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::INVOICED, $result);
    }

    public function testCalculatePaymentStatusWithNullContext(): void
    {
        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), null);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresTransactionAmounts(): void
    {
        $zeroAmountInvoiceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(0.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$zeroAmountInvoiceTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::INVOICED, $result);
    }
}
