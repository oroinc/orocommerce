<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\PaymentStatus\Calculator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\CanceledPartiallyPaymentStatusCalculator;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculationHelper;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class CanceledPartiallyPaymentStatusCalculatorTest extends TestCase
{
    private CanceledPartiallyPaymentStatusCalculator $calculator;

    protected function setUp(): void
    {
        $helper = new PaymentStatusCalculationHelper();
        $this->calculator = new CanceledPartiallyPaymentStatusCalculator($helper);
    }

    public function testCalculatePaymentStatusReturnsNullIfNoPaymentTransactions(): void
    {
        $context = new PaymentStatusCalculationContext([
            // no 'paymentTransactions'
        ]);

        self::assertNull($this->calculator->calculatePaymentStatus(new \stdClass(), $context));
    }

    public function testCalculatePaymentStatusReturnsNullIfNoSuccessfulTransactions(): void
    {
        $failedTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$failedTransaction]),
        ]);

        self::assertNull($this->calculator->calculatePaymentStatus(new \stdClass(), $context));
    }

    public function testCalculatePaymentStatusReturnsNullIfNoCanceledTransactions(): void
    {
        $authorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$authorizeTransaction]),
        ]);

        self::assertNull($this->calculator->calculatePaymentStatus(new \stdClass(), $context));
    }

    public function testCalculatePaymentStatusReturnsCanceledPartially(): void
    {
        $authorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $cancelTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CANCEL)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$authorizeTransaction, $cancelTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::CANCELED_PARTIALLY, $result);
    }

    public function testCalculatePaymentStatusReturnsNullIfCanceledAmountEqualsAuthorizedAmount(): void
    {
        $authorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $cancelTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CANCEL)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$authorizeTransaction, $cancelTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusReturnsNullIfCanceledAmountExceedsAuthorizedAmount(): void
    {
        $authorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $cancelTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CANCEL)
            ->setAmount(150.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$authorizeTransaction, $cancelTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresUnsuccessfulCancelTransactions(): void
    {
        $authorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $failedCancelTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CANCEL)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$authorizeTransaction, $failedCancelTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresCloneCancelTransactions(): void
    {
        $authorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $sourceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::VALIDATE)
            ->setReference('ref123');

        $cloneCancelTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CANCEL)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(true)
            ->setReference('ref123')
            ->setSourcePaymentTransaction($sourceTransaction);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$authorizeTransaction, $cloneCancelTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresCaptureTransactions(): void
    {
        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $cancelTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CANCEL)
            ->setAmount(30.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$captureTransaction, $cancelTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresChargeTransactions(): void
    {
        $chargeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CHARGE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $cancelTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CANCEL)
            ->setAmount(30.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$chargeTransaction, $cancelTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresPurchaseTransactions(): void
    {
        $purchaseTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $cancelTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CANCEL)
            ->setAmount(40.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([$purchaseTransaction, $cancelTransaction]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusWithMultipleTransactions(): void
    {
        $authorizeTransaction1 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(60.0)
            ->setActive(true)
            ->setSuccessful(true);

        $authorizeTransaction2 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(40.0)
            ->setActive(true)
            ->setSuccessful(true);

        $cancelTransaction1 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CANCEL)
            ->setAmount(30.0)
            ->setActive(true)
            ->setSuccessful(true);

        $cancelTransaction2 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CANCEL)
            ->setAmount(20.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'paymentTransactions' => new ArrayCollection([
                $authorizeTransaction1,
                $authorizeTransaction2,
                $cancelTransaction1,
                $cancelTransaction2,
            ]),
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::CANCELED_PARTIALLY, $result);
    }
}
