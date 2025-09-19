<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\PaymentStatus\Calculator;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\AuthorizedPaymentStatusCalculator;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculationHelper;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class AuthorizedPaymentStatusCalculatorTest extends TestCase
{
    private AuthorizedPaymentStatusCalculator $calculator;

    protected function setUp(): void
    {
        $helper = new PaymentStatusCalculationHelper();
        $this->calculator = new AuthorizedPaymentStatusCalculator($helper);
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

    public function testCalculatePaymentStatusReturnsAuthorized(): void
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
        self::assertEquals(PaymentStatuses::AUTHORIZED, $result);
    }

    public function testCalculatePaymentStatusReturnsNullIfNotAuthorized(): void
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
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusReturnsNullIfTransactionNotActive(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $transaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(false)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => [$transaction],
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusReturnsNullIfTransactionNotSuccessful(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $transaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(false);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => [$transaction],
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusReturnsNullIfActionIsNotAuthorize(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $transaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
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

    public function testCalculatePaymentStatusIgnoresCloneTransactions(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $sourceTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::VALIDATE)
            ->setReference('ref123');

        $cloneTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
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

    public function testCalculatePaymentStatusWithMultipleTransactionsOneValid(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $invalid = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $valid = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => [$invalid, $valid],
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::AUTHORIZED, $result);
    }

    public function testCalculatePaymentStatusWithMultipleTransactionsNoneValid(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $invalid1 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $invalid2 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => [$invalid1, $invalid2],
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertNull($result);
    }

    public function testCalculatePaymentStatusWithMultipleValidTransactions(): void
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
        self::assertEquals(PaymentStatuses::AUTHORIZED, $result);
    }

    public function testCalculatePaymentStatusWithExcessiveAuthorization(): void
    {
        $subtotal = (new Subtotal())->setAmount(100.0);

        $transaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(150.0)
            ->setActive(true)
            ->setSuccessful(true);

        $context = new PaymentStatusCalculationContext([
            'total' => $subtotal,
            'paymentTransactions' => [$transaction],
        ]);

        $result = $this->calculator->calculatePaymentStatus(new \stdClass(), $context);
        self::assertEquals(PaymentStatuses::AUTHORIZED, $result);
    }
}
