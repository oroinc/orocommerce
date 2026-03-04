<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\PaymentBundle\Action\PaymentTransactionPartialRefundAction;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Component\Action\Event\ExecuteActionEvent;
use Oro\Component\Action\Event\ExecuteActionEvents;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PaymentTransactionPartialRefundActionTest extends TestCase
{
    private PaymentMethodProviderInterface&MockObject $paymentMethodProvider;
    private PaymentTransactionProvider&MockObject $paymentTransactionProvider;
    private EventDispatcherInterface&MockObject $dispatcher;
    private LoggerInterface&MockObject $logger;
    private PaymentTransactionPartialRefundAction $action;

    #[\Override]
    protected function setUp(): void
    {
        $contextAccessor = new ContextAccessor();
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $router = $this->createMock(RouterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new PaymentTransactionPartialRefundAction(
            $contextAccessor,
            $this->paymentMethodProvider,
            $this->paymentTransactionProvider,
            $router
        );
        $this->action->setLogger($this->logger);
        $this->action->setDispatcher($this->dispatcher);
    }

    public function testInitializeRequiresAmountOption(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "amount" is missing');

        $this->action->initialize([
            'paymentTransaction' => new PaymentTransaction(),
        ]);
    }

    public function testInitializeRequiresPaymentTransactionOption(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "paymentTransaction" is missing');

        $this->action->initialize([
            'amount' => 50.00,
        ]);
    }

    public function testInitializeWithAllRequiredOptions(): void
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('test_payment_method');

        $this->action->initialize([
            'paymentTransaction' => $paymentTransaction,
            'amount' => 50.00,
        ]);

        // If no exception is thrown, the test passes
        self::assertTrue(true);
    }

    public function testExecuteCreatesRefundTransactionWithSpecifiedAmount(): void
    {
        $sourceTransaction = $this->createPaymentTransaction(10, PaymentMethodInterface::CAPTURE, 100.00, true);
        $refundTransaction = $this->createPaymentTransaction(20, PaymentMethodInterface::REFUND, 0, true);

        $options = [
            'paymentTransaction' => $sourceTransaction,
            'amount' => 50.12,
            'attribute' => new PropertyPath('result'),
        ];
        $context = new ActionData();

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('createPaymentTransactionByParentTransaction')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($sourceTransaction)
            )
            ->willReturn($refundTransaction);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod
            ->expects(self::once())
            ->method('execute')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($refundTransaction)
            )
            ->willReturn(['success' => true]);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('test_payment_method')
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('getPaymentMethod')
            ->with('test_payment_method')
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider
            ->expects(self::exactly(2))
            ->method('savePaymentTransaction')
            ->withConsecutive(
                [self::identicalTo($refundTransaction)],
                [self::identicalTo($sourceTransaction)]
            );

        $this->action->initialize($options);
        $this->action->execute($context);

        // Verify the refund transaction has the correct amount
        self::assertEquals('50.12', $refundTransaction->getAmount());

        // Verify the result was set in context
        self::assertEquals([
            'transaction' => 20,
            'successful' => true,
            'message' => null,
            'success' => true,
        ], $context['result']);
    }

    public function testExecuteWithDifferentPartialAmounts(): void
    {
        $sourceTransaction = $this->createPaymentTransaction(10, PaymentMethodInterface::CAPTURE, 200.00, true);
        $refundTransaction = $this->createPaymentTransaction(20, PaymentMethodInterface::REFUND, 0, true);

        $options = [
            'paymentTransaction' => $sourceTransaction,
            'amount' => 75.55,
            'attribute' => new PropertyPath('result'),
        ];
        $context = new ActionData();

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('createPaymentTransactionByParentTransaction')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($sourceTransaction)
            )
            ->willReturn($refundTransaction);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod
            ->expects(self::once())
            ->method('execute')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($refundTransaction)
            )
            ->willReturn([]);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('test_payment_method')
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('getPaymentMethod')
            ->with('test_payment_method')
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider
            ->expects(self::exactly(2))
            ->method('savePaymentTransaction')
            ->withConsecutive(
                [self::identicalTo($refundTransaction)],
                [self::identicalTo($sourceTransaction)]
            );

        $this->action->initialize($options);
        $this->action->execute($context);

        self::assertEquals('75.55', $refundTransaction->getAmount());
    }

    public function testExecuteWithTransactionOptions(): void
    {
        $sourceTransaction = $this->createPaymentTransaction(10, PaymentMethodInterface::CAPTURE, 100.00, true);
        $refundTransaction = $this->createPaymentTransaction(20, PaymentMethodInterface::REFUND, 0, true);

        $options = [
            'paymentTransaction' => $sourceTransaction,
            'amount' => 30.45,
            'attribute' => new PropertyPath('result'),
            'transactionOptions' => [
                'reason' => 'customer_request',
                'notes' => 'Partial refund requested by customer',
            ],
        ];
        $context = new ActionData();

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('createPaymentTransactionByParentTransaction')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($sourceTransaction)
            )
            ->willReturn($refundTransaction);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod
            ->expects(self::once())
            ->method('execute')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($refundTransaction)
            )
            ->willReturn(['gateway_response' => 'approved']);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('test_payment_method')
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('getPaymentMethod')
            ->with('test_payment_method')
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider
            ->expects(self::exactly(2))
            ->method('savePaymentTransaction')
            ->withConsecutive(
                [self::identicalTo($refundTransaction)],
                [self::identicalTo($sourceTransaction)]
            );

        $this->action->initialize($options);
        $this->action->execute($context);

        // Verify transaction options were set
        self::assertEquals(
            [
                'reason' => 'customer_request',
                'notes' => 'Partial refund requested by customer',
            ],
            $refundTransaction->getTransactionOptions()
        );

        // Verify the result in context
        self::assertEquals([
            'transaction' => 20,
            'successful' => true,
            'message' => null,
            'reason' => 'customer_request',
            'notes' => 'Partial refund requested by customer',
            'gateway_response' => 'approved',
        ], $context['result']);
    }

    public function testExecuteWithFailedRefund(): void
    {
        $sourceTransaction = $this->createPaymentTransaction(10, PaymentMethodInterface::CAPTURE, 100.00, true);
        $refundTransaction = $this->createPaymentTransaction(20, PaymentMethodInterface::REFUND, 50.00, false);

        $options = [
            'paymentTransaction' => $sourceTransaction,
            'amount' => 50.00,
            'attribute' => new PropertyPath('result'),
        ];
        $context = new ActionData();

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('createPaymentTransactionByParentTransaction')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($sourceTransaction)
            )
            ->willReturn($refundTransaction);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod
            ->expects(self::once())
            ->method('execute')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($refundTransaction)
            )
            ->willThrowException(new \Exception('Payment gateway error'));

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('test_payment_method')
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('getPaymentMethod')
            ->with('test_payment_method')
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider
            ->expects(self::exactly(2))
            ->method('savePaymentTransaction')
            ->withConsecutive(
                [self::identicalTo($refundTransaction)],
                [self::identicalTo($sourceTransaction)]
            );

        $this->action->initialize($options);
        $this->action->execute($context);

        // Verify the error result in context
        self::assertEquals([
            'transaction' => 20,
            'successful' => false,
            'message' => 'oro.payment.message.error',
        ], $context['result']);
    }

    public function testExecuteWhenPaymentMethodNotExists(): void
    {
        $sourceTransaction = $this->createPaymentTransaction(10, PaymentMethodInterface::CAPTURE, 100.00, true);
        $options = [
            'paymentTransaction' => $sourceTransaction,
            'amount' => 50.00,
            'attribute' => new PropertyPath('result'),
            'transactionOptions' => [
                'custom_field' => 'custom_value',
            ],
        ];
        $context = new ActionData();

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('test_payment_method')
            ->willReturn(false);

        $this->paymentTransactionProvider
            ->expects(self::never())
            ->method('createPaymentTransactionByParentTransaction');

        $this->action->initialize($options);
        $this->action->execute($context);

        // Verify the error result with custom transaction options
        self::assertEquals([
            'transaction' => 10,
            'successful' => false,
            'message' => 'oro.payment.message.error',
            'custom_field' => 'custom_value',
        ], $context['result']);
    }

    public function testExecuteWithPaymentMethodInstance(): void
    {
        $sourceTransaction = $this->createPaymentTransaction(10, PaymentMethodInterface::CAPTURE, 100.00, true);
        $refundTransaction = $this->createPaymentTransaction(20, PaymentMethodInterface::REFUND, 45.67, true);
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $options = [
            'paymentTransaction' => $sourceTransaction,
            'amount' => 45.67,
            'paymentMethodInstance' => $paymentMethod,
            'attribute' => new PropertyPath('result'),
        ];
        $context = new ActionData();

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('createPaymentTransactionByParentTransaction')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($sourceTransaction)
            )
            ->willReturn($refundTransaction);

        $paymentMethod
            ->expects(self::once())
            ->method('execute')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($refundTransaction)
            )
            ->willReturn(['response' => 'success']);

        // Payment method provider should not be called when paymentMethodInstance is provided
        $this->paymentMethodProvider
            ->expects(self::never())
            ->method('hasPaymentMethod');

        $this->paymentMethodProvider
            ->expects(self::never())
            ->method('getPaymentMethod');

        $this->paymentTransactionProvider
            ->expects(self::exactly(2))
            ->method('savePaymentTransaction')
            ->withConsecutive(
                [self::identicalTo($refundTransaction)],
                [self::identicalTo($sourceTransaction)]
            );

        $this->action->initialize($options);
        $this->action->execute($context);

        self::assertEquals('45.67', $refundTransaction->getAmount());

        // Verify the result in context
        self::assertEquals([
            'transaction' => 20,
            'successful' => true,
            'message' => null,
            'response' => 'success',
        ], $context['result']);
    }

    public function testExecuteWithPropertyPathAmount(): void
    {
        $sourceTransaction = $this->createPaymentTransaction(10, PaymentMethodInterface::CAPTURE, 100.00, true);
        $refundTransaction = $this->createPaymentTransaction(20, PaymentMethodInterface::REFUND, 25.75, true);

        $options = [
            'paymentTransaction' => $sourceTransaction,
            'amount' => new PropertyPath('refundAmount'),
            'attribute' => new PropertyPath('result'),
        ];
        $context = new ActionData(['refundAmount' => 25.75]);

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('createPaymentTransactionByParentTransaction')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($sourceTransaction)
            )
            ->willReturn($refundTransaction);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod
            ->expects(self::once())
            ->method('execute')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($refundTransaction)
            )
            ->willReturn([]);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('test_payment_method')
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('getPaymentMethod')
            ->with('test_payment_method')
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider
            ->expects(self::exactly(2))
            ->method('savePaymentTransaction')
            ->withConsecutive(
                [self::identicalTo($refundTransaction)],
                [self::identicalTo($sourceTransaction)]
            );

        $this->action->initialize($options);
        $this->action->execute($context);

        self::assertEquals('25.75', $refundTransaction->getAmount());
    }

    public function testExecuteDispatchesHandleBeforeAndHandleAfterEvents(): void
    {
        $sourceTransaction = $this->createPaymentTransaction(10, PaymentMethodInterface::CAPTURE, 100.00, true);
        $refundTransaction = $this->createPaymentTransaction(20, PaymentMethodInterface::REFUND, 50.00, true);

        $options = [
            'paymentTransaction' => $sourceTransaction,
            'amount' => 50.00,
            'attribute' => new PropertyPath('result'),
        ];
        $context = new ActionData();

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('createPaymentTransactionByParentTransaction')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($sourceTransaction)
            )
            ->willReturn($refundTransaction);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod
            ->expects(self::once())
            ->method('execute')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($refundTransaction)
            )
            ->willReturn([]);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('test_payment_method')
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('getPaymentMethod')
            ->with('test_payment_method')
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider
            ->expects(self::exactly(2))
            ->method('savePaymentTransaction')
            ->withConsecutive(
                [self::identicalTo($refundTransaction)],
                [self::identicalTo($sourceTransaction)]
            );

        // Verify that dispatcher is called for HANDLE_BEFORE and HANDLE_AFTER events
        $this->dispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    self::callback(function ($event) use ($context) {
                        return $event instanceof ExecuteActionEvent
                            && $event->getContext() === $context
                            && $event->getAction() === $this->action;
                    }),
                    self::identicalTo(ExecuteActionEvents::HANDLE_BEFORE)
                ],
                [
                    self::callback(function ($event) use ($context) {
                        return $event instanceof ExecuteActionEvent
                            && $event->getContext() === $context
                            && $event->getAction() === $this->action;
                    }),
                    self::identicalTo(ExecuteActionEvents::HANDLE_AFTER)
                ]
            );

        // Logger should not be called in success scenario
        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function testExecuteLogsErrorWhenPaymentMethodExecuteThrowsException(): void
    {
        $sourceTransaction = $this->createPaymentTransaction(10, PaymentMethodInterface::CAPTURE, 100.00, true);
        $refundTransaction = $this->createPaymentTransaction(20, PaymentMethodInterface::REFUND, 50.00, false);

        $options = [
            'paymentTransaction' => $sourceTransaction,
            'amount' => 50.00,
            'attribute' => new PropertyPath('result'),
        ];
        $context = new ActionData();

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('createPaymentTransactionByParentTransaction')
            ->with(
                self::identicalTo(PaymentMethodInterface::REFUND),
                self::identicalTo($sourceTransaction)
            )
            ->willReturn($refundTransaction);

        $exception = new \Exception('Payment gateway connection timeout');
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod
            ->expects(self::once())
            ->method('execute')
            ->with(
                PaymentMethodInterface::REFUND,
                self::identicalTo($refundTransaction)
            )
            ->willThrowException($exception);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('test_payment_method')
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('getPaymentMethod')
            ->with('test_payment_method')
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider
            ->expects(self::exactly(2))
            ->method('savePaymentTransaction')
            ->withConsecutive(
                [self::identicalTo($refundTransaction)],
                [self::identicalTo($sourceTransaction)]
            );

        // Verify that logger is called with the exception message
        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('Payment gateway connection timeout');

        // Dispatcher should still be called for both events
        $this->dispatcher
            ->expects(self::exactly(2))
            ->method('dispatch');

        $this->action->initialize($options);
        $this->action->execute($context);

        // Verify the error result in context
        self::assertEquals([
            'transaction' => 20,
            'successful' => false,
            'message' => 'oro.payment.message.error',
        ], $context['result']);
    }

    public function testExecuteDispatcherNotCalledWhenConditionIsFalse(): void
    {
        $sourceTransaction = $this->createPaymentTransaction(10, PaymentMethodInterface::CAPTURE, 100.00, true);

        $options = [
            'paymentTransaction' => $sourceTransaction,
            'amount' => 50.00,
            'attribute' => new PropertyPath('result'),
        ];
        $context = new ActionData();

        // Set a condition that evaluates to false
        $condition = $this->createMock(ExpressionInterface::class);
        $condition
            ->expects(self::once())
            ->method('evaluate')
            ->with(self::identicalTo($context))
            ->willReturn(false);

        $this->action->setCondition($condition);

        // Dispatcher should not be called when condition is false
        $this->dispatcher
            ->expects(self::never())
            ->method('dispatch');

        // Payment method provider should not be called
        $this->paymentMethodProvider
            ->expects(self::never())
            ->method('hasPaymentMethod');

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    private function createPaymentTransaction(
        int $id,
        string $action,
        float $amount,
        bool $successful
    ): PaymentTransaction {
        $paymentTransaction = new PaymentTransaction();
        ReflectionUtil::setId($paymentTransaction, $id);
        $paymentTransaction->setAction($action);
        $paymentTransaction->setAmount($amount);
        $paymentTransaction->setActive(true);
        $paymentTransaction->setSuccessful($successful);
        $paymentTransaction->setPaymentMethod('test_payment_method');
        $paymentTransaction->setCurrency('USD');

        return $paymentTransaction;
    }
}
