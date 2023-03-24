<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\PaymentBundle\Action\ValidateAction;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Component\ConfigExpression\ContextAccessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;

class ValidateActionTest extends \PHPUnit\Framework\TestCase
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

    /** @var ValidateAction */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new ValidateAction(
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
    public function testExecuteAction(array $data, array $expected)
    {
        $context = [];
        $options = $data['options'];

        $responseValue = $this->returnValue($data['response']);

        if ($data['response'] instanceof \Exception) {
            $responseValue = $this->throwException($data['response']);
        }

        $this->contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->action->initialize($options);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction(PaymentMethodInterface::VALIDATE)
            ->setPaymentMethod($options['paymentMethod']);

        $paymentMethod->expects(self::once())
            ->method('execute')
            ->with($paymentTransaction->getAction(), $paymentTransaction)
            ->will($responseValue);

        $this->paymentTransactionProvider->expects(self::once())
            ->method('createPaymentTransaction')
            ->with($options['paymentMethod'], PaymentMethodInterface::VALIDATE, $options['object'])
            ->willReturn($paymentTransaction);

        $this->paymentMethodProvider->expects(self::atLeastOnce())
            ->method('hasPaymentMethod')
            ->with($options['paymentMethod'])
            ->willReturn(true);

        $this->paymentMethodProvider->expects(self::atLeastOnce())
            ->method('getPaymentMethod')
            ->with($options['paymentMethod'])
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider->expects(self::once())
            ->method('savePaymentTransaction')
            ->with($paymentTransaction)
            ->willReturnCallback(function (PaymentTransaction $paymentTransaction) use ($options) {
                if (!empty($options['transactionOptions'])) {
                    self::assertEquals(
                        $options['transactionOptions'],
                        $paymentTransaction->getTransactionOptions()
                    );
                }
            });

        $this->contextAccessor->expects(self::once())
            ->method('setValue')
            ->with($context, $options['attribute'], $expected);

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

        $this->action->execute($context);
    }

    public function executeDataProvider(): array
    {
        return [
            'throw exception' => [
                'data' => [
                    'options' => [
                        'object' => new \stdClass(),
                        'attribute' => new PropertyPath('test'),
                        'paymentMethod' => self::PAYMENT_METHOD,
                        'transactionOptions' => [
                            'testOption' => 'testOption'
                        ],
                    ],
                    'response' => new \Exception(),
                ],
                'expected' => [
                    'paymentMethod' => self::PAYMENT_METHOD,
                    'errorUrl' => 'oro_payment_callback_error',
                    'returnUrl' => 'oro_payment_callback_return',
                    'testOption' => 'testOption',
                ]
            ],
            'default' => [
                'data' => [
                    'options' => [
                        'object' => new \stdClass(),
                        'attribute' => new PropertyPath('test'),
                        'paymentMethod' => self::PAYMENT_METHOD,
                        'transactionOptions' => [
                            'testOption' => 'testOption'
                        ],
                    ],
                    'response' => ['testResponse' => 'testResponse'],
                ],
                'expected' => [
                    'paymentMethod' => self::PAYMENT_METHOD,
                    'errorUrl' => 'oro_payment_callback_error',
                    'returnUrl' => 'oro_payment_callback_return',
                    'testResponse' => 'testResponse',
                    'testOption' => 'testOption',
                ]
            ],
            'without transactionOptions' => [
                'data' => [
                    'options' => [
                        'object' => new \stdClass(),
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
                ]
            ],
        ];
    }
}
