<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\PaymentBundle\Action\PurchaseAction;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class PurchaseActionTest extends TestCase
{
    private const string PAYMENT_METHOD = 'testPaymentMethod';

    private ContextAccessor&MockObject $contextAccessor;

    private PaymentMethodProviderInterface&MockObject $paymentMethodProvider;

    private PaymentTransactionProvider&MockObject $paymentTransactionProvider;

    private RouterInterface&MockObject $router;

    private LoggerInterface&MockObject $logger;

    private PaymentStatusManager&MockObject $paymentStatusManager;

    private PaymentStatusProviderInterface&MockObject $paymentStatusProvider;

    private PurchaseAction $action;

    private EventDispatcherInterface&MockObject $dispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->paymentStatusManager = $this->createMock(PaymentStatusManager::class);
        $this->paymentStatusProvider = $this->createMock(PaymentStatusProviderInterface::class);

        $this->action = new PurchaseAction(
            $this->contextAccessor,
            $this->paymentMethodProvider,
            $this->paymentTransactionProvider,
            $this->router
        );
        $this->action->setLogger($this->logger);
        $this->action->setDispatcher($this->dispatcher);
        $this->action->setPaymentStatusManager($this->paymentStatusManager);
        $this->action->setPaymentStatusProvider($this->paymentStatusProvider);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $data, array $expected): void
    {
        $context = [];
        $options = $data['options'];

        if ($data['response'] instanceof \Exception) {
            $responseValue = self::throwException($data['response']);
        } else {
            $responseValue = self::returnValue($data['response']);
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
        $paymentMethod
            ->method('getIdentifier')
            ->willReturn($options['paymentMethod']);
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
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ],
                [
                    'oro_payment_callback_return',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL,
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

    public function testExecuteWithPaymentAction(): void
    {
        $context = [];
        $options = [
            'object' => new \stdClass(),
            'amount' => 100.0,
            'currency' => 'USD',
            'attribute' => new PropertyPath('test'),
            'paymentAction' => PaymentMethodInterface::CHARGE,
            'paymentMethod' => self::PAYMENT_METHOD,
            'transactionOptions' => [
                'testOption' => 'testOption',
            ],
        ];

        $responseValue = self::returnValue(['testResponse' => 'testResponse']);

        $this->action->initialize($options);

        $this->contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction(PaymentMethodInterface::CHARGE)
            ->setPaymentMethod($options['paymentMethod']);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod
            ->method('getIdentifier')
            ->willReturn($options['paymentMethod']);
        $paymentMethod->expects(self::once())
            ->method('execute')
            ->with(PaymentMethodInterface::CHARGE, $paymentTransaction)
            ->will($responseValue);
        $paymentMethod->expects(self::once())
            ->method('supports')
            ->with(PaymentMethodInterface::VALIDATE)
            ->willReturn(false);

        $this->paymentTransactionProvider->expects(self::once())
            ->method('createPaymentTransaction')
            ->with($options['paymentMethod'], PaymentMethodInterface::CHARGE, $options['object'])
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
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ],
                [
                    'oro_payment_callback_return',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ]
            )
            ->willReturnArgument(0);

        $expected = [
            'paymentMethod' => self::PAYMENT_METHOD,
            'errorUrl' => 'oro_payment_callback_error',
            'returnUrl' => 'oro_payment_callback_return',
            'testResponse' => 'testResponse',
            'paymentMethodSupportsValidation' => false,
            'testOption' => 'testOption',
        ];
        $this->contextAccessor->expects(self::once())
            ->method('setValue')
            ->with($context, $options['attribute'], $expected);

        $this->action->execute($context);
    }

    public function testSourcePaymentTransactionNotFound(): void
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
        $paymentMethod
            ->method('getIdentifier')
            ->willReturn($options['paymentMethod']);
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
        string $status = PaymentStatuses::PAID_IN_FULL
    ): void {
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

        $this->paymentStatusManager->expects(self::exactly((int)$paymentTransaction->isSuccessful()))
            ->method('getPaymentStatus')
            ->willReturn((new PaymentStatus())->setPaymentStatus($status));

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod
            ->method('getIdentifier')
            ->willReturn($options['paymentMethod']);
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
            ->with(
                $context,
                $options['attribute'],
                self::callback(static function ($value) use ($expectedAttributes) {
                    foreach ($expectedAttributes as $expectedAttribute) {
                        self::assertContains($expectedAttribute, $value);
                    }

                    return true;
                })
            );

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
                PaymentStatuses::AUTHORIZED_PARTIALLY,
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

    public function testFailedExecuteDoesNotExposeContext(): void
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
        $paymentMethod
            ->method('getIdentifier')
            ->willReturn($options['paymentMethod']);
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
                self::isType('string'),
                self::isType('array')
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithPaymentMethodInstance(): void
    {
        $context = [];
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $data = [
            'options' => [
                'object' => new \stdClass(),
                'amount' => 100.0,
                'currency' => 'USD',
                'attribute' => new PropertyPath('test'),
                'paymentMethodInstance' => $paymentMethod,
                'transactionOptions' => [
                    'testOption' => 'testOption',
                ],
            ],
            'response' => ['testResponse' => 'testResponse'],
        ];
        $options = $data['options'];

        $this->action->initialize($options);

        $this->contextAccessor
            ->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setPaymentMethod(self::PAYMENT_METHOD);

        $paymentMethod
            ->method('getIdentifier')
            ->willReturn(self::PAYMENT_METHOD);
        $paymentMethod
            ->expects(self::once())
            ->method('execute')
            ->with(PaymentMethodInterface::PURCHASE, $paymentTransaction)
            ->willReturn($data['response']);
        $paymentMethod
            ->expects(self::once())
            ->method('supports')
            ->with(PaymentMethodInterface::VALIDATE)
            ->willReturn(false);

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('createPaymentTransaction')
            ->with(self::PAYMENT_METHOD, PaymentMethodInterface::PURCHASE, $options['object'])
            ->willReturn($paymentTransaction);

        $this->paymentMethodProvider
            ->expects(self::never())
            ->method('hasPaymentMethod');

        $this->paymentMethodProvider
            ->expects(self::never())
            ->method('getPaymentMethod');

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('savePaymentTransaction')
            ->with($paymentTransaction)
            ->willReturnCallback(function (PaymentTransaction $paymentTransaction) use ($options) {
                self::assertEquals($options['amount'], $paymentTransaction->getAmount());
                self::assertEquals($options['currency'], $paymentTransaction->getCurrency());
                self::assertEquals(
                    $options['transactionOptions'],
                    $paymentTransaction->getTransactionOptions()
                );
            });

        $this->router
            ->expects(self::exactly(2))
            ->method('generate')
            ->withConsecutive(
                [
                    'oro_payment_callback_error',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ],
                [
                    'oro_payment_callback_return',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ]
            )
            ->willReturnArgument(0);

        $expected = [
            'paymentMethod' => self::PAYMENT_METHOD,
            'errorUrl' => 'oro_payment_callback_error',
            'returnUrl' => 'oro_payment_callback_return',
            'testResponse' => 'testResponse',
            'paymentMethodSupportsValidation' => false,
            'testOption' => 'testOption',
        ];
        $this->contextAccessor
            ->expects(self::once())
            ->method('setValue')
            ->with($context, $options['attribute'], $expected);

        $this->action->execute($context);
    }

    public function testSourcePaymentTransactionWithNullPaymentStatusManager(): void
    {
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

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setPaymentMethod(self::PAYMENT_METHOD)
            ->setSuccessful(true);

        $sourcePaymentTransaction = new PaymentTransaction();

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

        $this->paymentStatusProvider->expects(self::once())
            ->method('getPaymentStatus')
            ->with($options['object'])
            ->willReturn(PaymentStatuses::AUTHORIZED_PARTIALLY);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod
            ->method('getIdentifier')
            ->willReturn($options['paymentMethod']);
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
            ->with(
                $context,
                $options['attribute'],
                self::callback(static function ($value) {
                    self::assertContains('purchaseSuccessful', array_keys($value));
                    self::assertContains('purchasePartial', array_keys($value));
                    self::assertTrue($value['purchaseSuccessful']);
                    self::assertTrue($value['purchasePartial']);
                    return true;
                })
            );

        // Create action without PaymentStatusManager for BC layer
        $action = new PurchaseAction(
            $this->contextAccessor,
            $this->paymentMethodProvider,
            $this->paymentTransactionProvider,
            $this->router
        );
        $action->setLogger($this->logger);
        $action->setDispatcher($this->dispatcher);
        $action->setPaymentStatusProvider($this->paymentStatusProvider);

        $action->initialize($options);
        $action->execute($context);
    }

    public function testIsPaidPartiallyWithNullPaymentStatusManagerNotPartial(): void
    {
        $options = [
            'object' => new \stdClass(),
            'amount' => 100.0,
            'currency' => 'USD',
            'attribute' => new PropertyPath('test'),
            'paymentMethod' => self::PAYMENT_METHOD,
        ];

        $context = [];

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setPaymentMethod(self::PAYMENT_METHOD)
            ->setSuccessful(true);

        $this->contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->paymentTransactionProvider->expects(self::once())
            ->method('createPaymentTransaction')
            ->willReturn($paymentTransaction);

        $this->paymentStatusProvider->expects(self::once())
            ->method('getPaymentStatus')
            ->with($options['object'])
            ->willReturn(PaymentStatuses::PAID_IN_FULL);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod
            ->method('getIdentifier')
            ->willReturn($options['paymentMethod']);
        $paymentMethod->expects(self::once())
            ->method('supports')
            ->with(PaymentMethodInterface::VALIDATE)
            ->willReturn(false);
        $paymentMethod->expects(self::once())
            ->method('execute')
            ->willReturn([]);

        $this->mockPaymentMethodProvider($paymentMethod, $options['paymentMethod']);

        $this->contextAccessor->expects(self::once())
            ->method('setValue')
            ->with(
                $context,
                $options['attribute'],
                self::callback(static function ($value) {
                    self::assertArrayNotHasKey('purchasePartial', $value);
                    return true;
                })
            );

        // Create action without PaymentStatusManager for BC layer
        $action = new PurchaseAction(
            $this->contextAccessor,
            $this->paymentMethodProvider,
            $this->paymentTransactionProvider,
            $this->router
        );
        $action->setLogger($this->logger);
        $action->setDispatcher($this->dispatcher);
        $action->setPaymentStatusProvider($this->paymentStatusProvider);

        $action->initialize($options);
        $action->execute($context);
    }
}
