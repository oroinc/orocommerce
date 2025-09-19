<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\PaymentStatus\Calculator;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\AuthorizedPartiallyPaymentStatusCalculator;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculationHelper;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use PHPUnit\Framework\TestCase;

final class AuthorizedPartiallyPaymentStatusCalculatorTest extends TestCase
{
    private AuthorizedPartiallyPaymentStatusCalculator $calculator;

    protected function setUp(): void
    {
        $helper = new PaymentStatusCalculationHelper();
        $this->calculator = new AuthorizedPartiallyPaymentStatusCalculator($helper);
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

    public function testCalculatePaymentStatusReturnsAuthorizedPartially(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $transaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => [$transaction],
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::AUTHORIZED_PARTIALLY, $result);
    }

    public function testCalculatePaymentStatusReturnsNullIfFullyAuthorized(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $transaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => [$transaction],
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresInactiveTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $inactiveTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(false)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => [$inactiveTransaction],
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresUnsuccessfulTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $unsuccessfulTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => [$unsuccessfulTransaction],
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresNonAuthorizeTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => [$captureTransaction],
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusIgnoresCloneTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $sourceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::VALIDATE)
            ->setReference('ref123');

        $cloneTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(true)
            ->setReference('ref123')
            ->setSourcePaymentTransaction($sourceTransaction);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => [$cloneTransaction],
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusWithMultiplePartialTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $transaction1 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(30.0)
            ->setActive(true)
            ->setSuccessful(true);

        $transaction2 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(40.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => [$transaction1, $transaction2],
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::AUTHORIZED_PARTIALLY, $result);
    }

    public function testCalculatePaymentStatusWithMultipleTransactionsEqualToTotal(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $transaction1 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(60.0)
            ->setActive(true)
            ->setSuccessful(true);

        $transaction2 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(40.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => [$transaction1, $transaction2],
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }
}
