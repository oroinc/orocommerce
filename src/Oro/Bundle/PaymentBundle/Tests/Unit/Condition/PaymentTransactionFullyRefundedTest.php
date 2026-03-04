<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

use Oro\Bundle\PaymentBundle\Condition\PaymentTransactionFullyRefunded;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionAmountAvailableToRefundProvider;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PaymentTransactionFullyRefundedTest extends TestCase
{
    private PaymentTransactionAmountAvailableToRefundProvider&MockObject $transactionDataProvider;
    private PaymentTransactionFullyRefunded $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->transactionDataProvider = $this->createMock(
            PaymentTransactionAmountAvailableToRefundProvider::class
        );

        $this->condition = new PaymentTransactionFullyRefunded($this->transactionDataProvider);
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testGetName(): void
    {
        self::assertEquals('payment_transaction_was_fully_refunded', $this->condition->getName());
    }

    public function testInitializeThrowsExceptionWhenTransactionOptionIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "transaction" option');

        $this->condition->initialize([]);
    }

    public function testInitializeThrowsExceptionWhenTransactionIsNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "transaction" option');

        $this->condition->initialize(['transaction' => null]);
    }

    public function testInitializeWithTransaction(): void
    {
        $transaction = $this->createPaymentTransaction(1, 100.00);

        $this->condition->initialize([
            'transaction' => $transaction
        ]);

        // If no exception is thrown, the test passes
        self::assertTrue(true);
    }

    public function testInitializeWithPropertyPath(): void
    {
        $result = $this->condition->initialize([
            'transaction' => new PropertyPath('payment.transaction')
        ]);

        self::assertInstanceOf(PaymentTransactionFullyRefunded::class, $result);
    }

    public function testEvaluateReturnsTrueWhenFullyRefunded(): void
    {
        $transaction = $this->createPaymentTransaction(1, 100.00);
        $context = [];

        $this->transactionDataProvider
            ->expects(self::once())
            ->method('getAvailableAmountToRefund')
            ->with(self::identicalTo($transaction))
            ->willReturn(0.0);

        $this->condition->initialize(['transaction' => $transaction]);

        self::assertTrue($this->condition->evaluate($context));
    }

    public function testEvaluateReturnsFalseWhenPartiallyRefunded(): void
    {
        $transaction = $this->createPaymentTransaction(1, 100.00);
        $context = [];

        $this->transactionDataProvider
            ->expects(self::once())
            ->method('getAvailableAmountToRefund')
            ->with(self::identicalTo($transaction))
            ->willReturn(50.00);

        $this->condition->initialize(['transaction' => $transaction]);

        self::assertFalse($this->condition->evaluate($context));
    }

    public function testEvaluateReturnsFalseWhenNotRefunded(): void
    {
        $transaction = $this->createPaymentTransaction(1, 100.00);
        $context = [];

        $this->transactionDataProvider
            ->expects(self::once())
            ->method('getAvailableAmountToRefund')
            ->with(self::identicalTo($transaction))
            ->willReturn(100.00);

        $this->condition->initialize(['transaction' => $transaction]);

        self::assertFalse($this->condition->evaluate($context));
    }

    public function testEvaluateWithPropertyPath(): void
    {
        $transaction = $this->createPaymentTransaction(1, 100.00);
        $context = ['payment' => ['transaction' => $transaction]];

        $this->transactionDataProvider
            ->expects(self::once())
            ->method('getAvailableAmountToRefund')
            ->with(self::identicalTo($transaction))
            ->willReturn(0.0);

        $this->condition->initialize([
            'transaction' => new PropertyPath('payment.transaction')
        ]);

        self::assertTrue($this->condition->evaluate($context));
    }

    public function testEvaluateWithZeroAmount(): void
    {
        $transaction = $this->createPaymentTransaction(1, 0.00);
        $context = [];

        // Zero available to refund means fully refunded
        $this->transactionDataProvider
            ->expects(self::once())
            ->method('getAvailableAmountToRefund')
            ->with(self::identicalTo($transaction))
            ->willReturn(0.0);

        $this->condition->initialize(['transaction' => $transaction]);

        self::assertTrue($this->condition->evaluate($context));
    }

    public function testEvaluateAddsErrorWhenConditionFails(): void
    {
        $transaction = $this->createPaymentTransaction(1, 100.00);
        $context = [];
        $errors = new \ArrayObject();

        $this->transactionDataProvider
            ->expects(self::once())
            ->method('getAvailableAmountToRefund')
            ->with(self::identicalTo($transaction))
            ->willReturn(50.00);

        $this->condition->initialize(['transaction' => $transaction]);
        $this->condition->setMessage('Transaction is not fully refunded');

        $result = $this->condition->evaluate($context, $errors);

        self::assertFalse($result);
        self::assertCount(1, $errors);
        self::assertEquals('Transaction is not fully refunded', $errors[0]['message']);
    }

    public function testEvaluateDoesNotAddErrorWhenConditionSucceeds(): void
    {
        $transaction = $this->createPaymentTransaction(1, 100.00);
        $context = [];
        $errors = new \ArrayObject();

        $this->transactionDataProvider
            ->expects(self::once())
            ->method('getAvailableAmountToRefund')
            ->with(self::identicalTo($transaction))
            ->willReturn(0.0);

        $this->condition->initialize(['transaction' => $transaction]);

        $result = $this->condition->evaluate($context, $errors);

        self::assertTrue($result);
        self::assertCount(0, $errors);
    }

    private function createPaymentTransaction(int $id, float $amount): PaymentTransaction
    {
        $transaction = new PaymentTransaction();
        ReflectionUtil::setId($transaction, $id);
        $transaction->setAmount($amount);
        $transaction->setCurrency('USD');
        $transaction->setAction('capture');
        $transaction->setPaymentMethod('test_method');
        $transaction->setSuccessful(true);

        return $transaction;
    }
}
