<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\PaymentBundle\Action\PaymentTransactionRefundAction;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class PaymentTransactionRefundActionTest extends AbstractActionTest
{
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

        $this->contextAccessor
            ->expects(static::any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->paymentTransactionProvider
            ->expects(static::once())
            ->method('createPaymentTransactionByParentTransaction')
            ->with(PaymentMethodInterface::REFUND, $capturePaymentTransaction)
            ->willReturn($cancelPaymentTransaction);

        if ($data['response'] instanceof \Exception) {
            $responseValue = $this->throwException($data['response']);
        } else {
            $responseValue = $this->returnValue($data['response']);
        }

        /** @var PaymentMethodInterface|\PHPUnit\Framework\MockObject\MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(static::once())
            ->method('execute')
            ->with(PaymentMethodInterface::REFUND, $cancelPaymentTransaction)
            ->will($responseValue);

        $this->paymentMethodProvider
            ->method('hasPaymentMethod')
            ->with($capturePaymentTransaction->getPaymentMethod())
            ->willReturn(true);

        $this->paymentMethodProvider
            ->method('getPaymentMethod')
            ->with($capturePaymentTransaction->getPaymentMethod())
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider
            ->expects(static::exactly(2))
            ->method('savePaymentTransaction')
            ->withConsecutive(
                [$cancelPaymentTransaction],
                [$capturePaymentTransaction]
            );

        $this->contextAccessor
            ->expects(static::once())
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

    /**
     * @dataProvider executeWrongOptionsDataProvider
     */
    public function testExecuteWrongOptions(array $options)
    {
        $this->expectException(\Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException::class);
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

    protected function getAction()
    {
        return new PaymentTransactionRefundAction(
            $this->contextAccessor,
            $this->paymentMethodProvider,
            $this->paymentTransactionProvider,
            $this->router
        );
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

        $this->paymentMethodProvider->expects($this->once())->method('hasPaymentMethod')->willReturn(false);
        $this->contextAccessor->method('getValue')->will($this->returnArgument(1));

        $this->contextAccessor
            ->expects($this->once())
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
}
