<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\PaymentBundle\Action\PaymentTransactionRefundAction;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Component\ConfigExpression\ContextAccessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;

class PaymentTransactionRefundActionTest extends \PHPUnit\Framework\TestCase
{
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

    /** @var PaymentTransactionRefundAction */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new PaymentTransactionRefundAction(
            $this->contextAccessor,
            $this->paymentMethodProvider,
            $this->paymentTransactionProvider,
            $this->router
        );
        $this->action->setLogger($this->logger);
        $this->action->setDispatcher($this->dispatcher);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $data, array $expected)
    {
        /** @var PaymentTransaction $capturePaymentTransaction */
        $capturePaymentTransaction = $data['options']['paymentTransaction'];
        /** @var PaymentTransaction $cancelPaymentTransaction */
        $cancelPaymentTransaction = $data['refundPaymentTransaction'];
        $options = $data['options'];
        $context = [];

        $this->contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->paymentTransactionProvider->expects(self::once())
            ->method('createPaymentTransactionByParentTransaction')
            ->with(PaymentMethodInterface::REFUND, $capturePaymentTransaction)
            ->willReturn($cancelPaymentTransaction);

        if ($data['response'] instanceof \Exception) {
            $responseValue = $this->throwException($data['response']);
        } else {
            $responseValue = $this->returnValue($data['response']);
        }

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(self::once())
            ->method('execute')
            ->with(PaymentMethodInterface::REFUND, $cancelPaymentTransaction)
            ->will($responseValue);

        $this->paymentMethodProvider->expects(self::any())
            ->method('hasPaymentMethod')
            ->with($capturePaymentTransaction->getPaymentMethod())
            ->willReturn(true);

        $this->paymentMethodProvider->expects(self::any())
            ->method('getPaymentMethod')
            ->with($capturePaymentTransaction->getPaymentMethod())
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider->expects(self::exactly(2))
            ->method('savePaymentTransaction')
            ->withConsecutive(
                [$cancelPaymentTransaction],
                [$capturePaymentTransaction]
            );

        $this->contextAccessor->expects(self::once())
            ->method('setValue')
            ->with($context, $options['attribute'], $expected);

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function executeDataProvider(): array
    {
        return [
            'default' => [
                'data' => [
                    'refundPaymentTransaction' => $this->getPaymentTransaction(PaymentMethodInterface::REFUND, true),
                    'options' => [
                        'paymentTransaction' => $this->getPaymentTransaction(PaymentMethodInterface::CAPTURE, true),
                        'attribute' => new PropertyPath('test'),
                        'transactionOptions' => [
                            'testOption' => 'testOption',
                        ],
                    ],
                    'response' => ['testResponse' => 'testResponse'],
                ],
                'expected' => [
                    'transaction' => null,
                    'successful' => true,
                    'message' => null,
                    'testOption' => 'testOption',
                    'testResponse' => 'testResponse',
                ],
            ],
            'throw exception' => [
                'data' => [
                    'refundPaymentTransaction' => $this->getPaymentTransaction(PaymentMethodInterface::REFUND, false),
                    'options' => [
                        'paymentTransaction' => $this->getPaymentTransaction(PaymentMethodInterface::CAPTURE, true),
                        'attribute' => new PropertyPath('test'),
                        'transactionOptions' => [
                            'testOption' => 'testOption',
                        ],
                    ],
                    'response' => new \Exception(),
                ],
                'expected' => [
                    'transaction' => null,
                    'successful' => false,
                    'message' => 'oro.payment.message.error',
                    'testOption' => 'testOption',
                ],
            ],
        ];
    }

    /**
     * @dataProvider executeWrongOptionsDataProvider
     */
    public function testExecuteWrongOptions(array $options)
    {
        $this->expectException(UndefinedOptionsException::class);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('testPaymentMethodType');

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    public function executeWrongOptionsDataProvider(): array
    {
        return [
            [['someOption' => 'someValue']],
            [['object' => 'someValue']],
            [['amount' => 'someAmount']],
            [['currency' => 'someCurrency']],
            [['paymentMethod' => 'somePaymentMethod']],
        ];
    }

    public function testExecuteFailedWhenPaymentMethodNotExists()
    {
        $context = [];
        $options = [
            'paymentTransaction' => new PaymentTransaction(),
            'attribute' => new PropertyPath('test'),
            'transactionOptions' => [
                'testOption' => 'testOption',
            ],
        ];

        $this->paymentMethodProvider->expects(self::once())
            ->method('hasPaymentMethod')
            ->willReturn(false);
        $this->contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->contextAccessor->expects(self::once())
            ->method('setValue')
            ->with(
                $context,
                $options['attribute'],
                [
                    'transaction' => null,
                    'successful' => false,
                    'message' => 'oro.payment.message.error',
                    'testOption' => 'testOption',
                ]
            );

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    private function getPaymentTransaction(string $action, bool $successful): PaymentTransaction
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setAction($action);
        $paymentTransaction->setAmount(100);
        $paymentTransaction->setActive(true);
        $paymentTransaction->setSuccessful($successful);
        $paymentTransaction->setPaymentMethod('testPaymentMethodType');

        return $paymentTransaction;
    }
}
