<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\PaymentBundle\Action\PurchaseAction;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Component\ConfigExpression\ContextAccessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;

class PurchaseActionTest extends \PHPUnit\Framework\TestCase
{
    private const PAYMENT_METHOD = 'testPaymentMethod';

    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodProvider;

    /** @var PaymentTransactionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTransactionProvider;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var PaymentStatusProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentStatusProvider;

    /** @var PurchaseAction */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->paymentStatusProvider = $this->createMock(PaymentStatusProviderInterface::class);

        $this->action = new PurchaseAction(
            $this->contextAccessor,
            $this->paymentMethodProvider,
            $this->paymentTransactionProvider,
            $this->router
        );
        $this->action->setLogger($this->logger);
        $this->action->setDispatcher($this->dispatcher);
        $this->action->setPaymentStatusProvider($this->paymentStatusProvider);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $data, array $expected)
    {
        $context = [];
        $options = $data['options'];

        if ($data['response'] instanceof \Exception) {
            $responseValue = $this->throwException($data['response']);
        } else {
            $responseValue = $this->returnValue($data['response']);
        }

        $this->action->initialize($options);

        $this->contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setPaymentMethod($options['paymentMethod']);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(self::once())
            ->method('execute')
            ->with(PaymentMethodInterface::PURCHASE, $paymentTransaction)
            ->will($responseValue);
        $paymentMethod->expects(self::once())
            ->method('supports')
            ->with(PaymentMethodInterface::VALIDATE)
            ->willReturn(false);

        $this->paymentTransactionProvider->expects(self::once())
            ->method('createPaymentTransaction')
            ->with($options['paymentMethod'], PaymentMethodInterface::PURCHASE, $options['object'])
            ->willReturn($paymentTransaction);

        $this->mockPaymentMethodProvider($paymentMethod, $options['paymentMethod']);

        $this->paymentTransactionProvider->expects(self::once())
            ->method('savePaymentTransaction')
            ->with($paymentTransaction)
            ->willReturnCallback(function (PaymentTransaction $paymentTransaction) use ($options) {
                self::assertEquals($options['amount'], $paymentTransaction->getAmount());
                self::assertEquals($options['currency'], $paymentTransaction->getCurrency());
                if (!empty($options['transactionOptions'])) {
                    self::assertEquals(
                        $options['transactionOptions'],
                        $paymentTransaction->getTransactionOptions()
                    );
                }
            });

        $this->router->expects(self::exactly(2))
            ->method('generate')
            ->withConsecutive(
                [
                    'oro_payment_callback_error',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    ],
                    RouterInterface::ABSOLUTE_URL
                ],
                [
                    'oro_payment_callback_return',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    ],
                    RouterInterface::ABSOLUTE_URL
                ]
            )
            ->willReturnArgument(0);

        $this->contextAccessor->expects(self::once())
            ->method('setValue')
            ->with($context, $options['attribute'], $expected);

        $this->action->execute($context);
    }

    public function executeDataProvider(): array
    {
        return [
            'default' => [
                'data' => [
                    'options' => [
                        'object' => new \stdClass(),
                        'amount' => 100.0,
                        'currency' => 'USD',
                        'attribute' => new PropertyPath('test'),
                        'paymentMethod' => self::PAYMENT_METHOD,
                        'transactionOptions' => [
                            'testOption' => 'testOption',
                        ],
                    ],
                    'response' => ['testResponse' => 'testResponse'],
                ],
                'expected' => [
                    'paymentMethod' => self::PAYMENT_METHOD,
                    'errorUrl' => 'oro_payment_callback_error',
                    'returnUrl' => 'oro_payment_callback_return',
                    'testResponse' => 'testResponse',
                    'paymentMethodSupportsValidation' => false,
                    'testOption' => 'testOption',
                ],
            ],
            'without transactionOptions' => [
                'data' => [
                    'options' => [
                        'object' => new \stdClass(),
                        'amount' => 100.0,
                        'currency' => 'USD',
                        'attribute' => new PropertyPath('test'),
                        'paymentMethod' => self::PAYMENT_METHOD,
                    ],
                    'response' => ['testResponse' => 'testResponse'],
                ],
                'expected' => [
                    'paymentMethod' => self::PAYMENT_METHOD,
                    'errorUrl' => 'oro_payment_callback_error',
                    'returnUrl' => 'oro_payment_callback_return',
                    'testResponse' => 'testResponse',
                    'paymentMethodSupportsValidation' => false,
                ],
            ],
            'throw exception' => [
                'data' => [
                    'options' => [
                        'object' => new \stdClass(),
                        'amount' => 100.0,
                        'currency' => 'USD',
                        'attribute' => new PropertyPath('test'),
                        'paymentMethod' => self::PAYMENT_METHOD,
                        'transactionOptions' => [
                            'testOption' => 'testOption',
                        ],
                    ],
                    'response' => new \Exception(),
                ],
                'expected' => [
                    'paymentMethod' => self::PAYMENT_METHOD,
                    'errorUrl' => 'oro_payment_callback_error',
                    'returnUrl' => 'oro_payment_callback_return',
                    'paymentMethodSupportsValidation' => false,
                    'testOption' => 'testOption',
                ],
            ],
        ];
    }

    public function testSourcePaymentTransactionNotFound()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Validation payment transaction not found');

        $options = [
            'object' => new \stdClass(),
            'amount' => 100.0,
            'currency' => 'USD',
            'attribute' => new PropertyPath('test'),
            'paymentMethod' => self::PAYMENT_METHOD,
            'transactionOptions' => [
                'testOption' => 'testOption',
            ],
        ];

        $this->contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setPaymentMethod(self::PAYMENT_METHOD);

        $this->paymentTransactionProvider->expects(self::once())
            ->method('createPaymentTransaction')
            ->with($options['paymentMethod'], PaymentMethodInterface::PURCHASE, $options['object'])
            ->willReturn($paymentTransaction);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(self::once())
            ->method('supports')
            ->with(PaymentMethodInterface::VALIDATE)
            ->willReturn(true);

        $this->mockPaymentMethodProvider($paymentMethod, $options['paymentMethod']);

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    /**
     * @dataProvider sourcePaymentTransactionProvider
     */
    public function testSourcePaymentTransaction(
        PaymentTransaction $paymentTransaction,
        PaymentTransaction $sourcePaymentTransaction,
        array $expectedAttributes = [],
        array $expectedSourceTransactionProperties = [],
        string $status = PaymentStatusProvider::FULL
    ) {
        $options = [
            'object' => new \stdClass(),
            'amount' => 100.0,
            'currency' => 'USD',
            'attribute' => new PropertyPath('test'),
            'paymentMethod' => self::PAYMENT_METHOD,
            'transactionOptions' => [
                'testOption' => 'testOption',
            ],
        ];

        $context = [];

        $this->contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->paymentTransactionProvider->expects(self::once())
            ->method('createPaymentTransaction')
            ->with($options['paymentMethod'], PaymentMethodInterface::PURCHASE, $options['object'])
            ->willReturn($paymentTransaction);

        $this->paymentTransactionProvider->expects(self::once())
            ->method('getActiveValidatePaymentTransaction')
            ->willReturn($sourcePaymentTransaction);

        $this->paymentStatusProvider->expects(self::exactly((int)$paymentTransaction->isSuccessful()))
            ->method('getPaymentStatus')
            ->willReturn($status);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(self::once())
            ->method('supports')
            ->with(PaymentMethodInterface::VALIDATE)
            ->willReturn(true);
        $paymentMethod->expects(self::once())
            ->method('execute')
            ->with($paymentTransaction->getAction(), $paymentTransaction)
            ->willReturn([]);

        $this->mockPaymentMethodProvider($paymentMethod, $options['paymentMethod']);

        $this->contextAccessor->expects(self::once())
            ->method('setValue')
            ->with($context, $options['attribute'], $this->callback(function ($value) use ($expectedAttributes) {
                foreach ($expectedAttributes as $expectedAttribute) {
                    self::assertContains($expectedAttribute, $value);
                }

                return true;
            }));

        $this->action->initialize($options);
        $this->action->execute($context);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($expectedSourceTransactionProperties as $path => $expectedValue) {
            $actualValue = $propertyAccessor->getValue($sourcePaymentTransaction, $path);
            self::assertSame($expectedValue, $actualValue, $path);
        }
    }

    public function sourcePaymentTransactionProvider(): array
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setPaymentMethod(self::PAYMENT_METHOD);

        $successfulTransaction = clone $paymentTransaction;
        $successfulTransaction->setSuccessful(true);
        $unsuccessfulTransaction = clone $paymentTransaction;
        $unsuccessfulTransaction->setSuccessful(false);

        return [
            'without saveForLaterUse deactivates source transaction' => [
                $paymentTransaction,
                (new PaymentTransaction())->setActive(true),
                [],
                [
                    'active' => false,
                ],
            ],
            'saveForLaterUse leaves source transaction active' => [
                $paymentTransaction,
                (new PaymentTransaction())->setActive(true)->setTransactionOptions(['saveForLaterUse' => true]),
                [],
                [
                    'active' => true,
                ],
            ],
            'successful transaction with validation' => [
                $successfulTransaction,
                new PaymentTransaction(),
                [
                    'purchaseSuccessful' => true,
                ],
            ],
            'successful transaction partially paid' => [
                $successfulTransaction,
                new PaymentTransaction(),
                [
                    'purchasePartial' => true,
                ],
                [],
                PaymentStatusProvider::AUTHORIZED_PARTIALLY
            ],
            'unsuccessful transaction with validation' => [
                $unsuccessfulTransaction,
                new PaymentTransaction(),
                [
                    'purchaseSuccessful' => false,
                ],
            ],
        ];
    }

    public function testFailedExecuteDoesNotExposeContext()
    {
        $options = [
            'object' => new \stdClass(),
            'amount' => 100.0,
            'currency' => 'USD',
            'paymentMethod' => self::PAYMENT_METHOD,
            'transactionOptions' => [
                'testOption' => 'testOption',
            ],
        ];

        $this->contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setPaymentMethod(self::PAYMENT_METHOD);

        $this->paymentTransactionProvider->expects(self::once())
            ->method('createPaymentTransaction')
            ->willReturn($paymentTransaction);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(self::once())
            ->method('supports')
            ->with(PaymentMethodInterface::VALIDATE)
            ->willReturn(false);
        $paymentMethod->expects(self::once())
            ->method('execute')
            ->willThrowException(new \Exception());

        $this->mockPaymentMethodProvider($paymentMethod, $options['paymentMethod']);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                $this->isType('string'),
                $this->isType('array')
            );

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    private function mockPaymentMethodProvider(PaymentMethodInterface $paymentMethod, string $identifier): void
    {
        $this->paymentMethodProvider->expects(self::atLeastOnce())
            ->method('hasPaymentMethod')
            ->with($identifier)
            ->willReturn(true);
        $this->paymentMethodProvider->expects(self::atLeastOnce())
            ->method('getPaymentMethod')
            ->with($identifier)
            ->willReturn($paymentMethod);
    }
}
