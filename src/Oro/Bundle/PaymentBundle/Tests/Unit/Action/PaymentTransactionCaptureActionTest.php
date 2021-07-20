<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\PaymentBundle\Action\PaymentTransactionCaptureAction;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodWithPostponedCaptureInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class PaymentTransactionCaptureActionTest extends AbstractActionTest
{
    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $data, array $expected)
    {
        /** @var PaymentTransaction $authorizationPaymentTransaction */
        $authorizationPaymentTransaction = $data['options']['paymentTransaction'];
        $capturePaymentTransaction = $data['capturePaymentTransaction'];
        $options = $data['options'];
        $context = [];

        $this->contextAccessor
            ->expects(static::any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->paymentTransactionProvider
            ->expects(static::once())
            ->method('createPaymentTransactionByParentTransaction')
            ->with(PaymentMethodInterface::CAPTURE, $authorizationPaymentTransaction)
            ->willReturn($capturePaymentTransaction);

        $responseValue = $this->returnValue($data['response']);

        if ($data['response'] instanceof \Exception) {
            $responseValue = $this->throwException($data['response']);
        }

        /** @var PaymentMethodInterface|\PHPUnit\Framework\MockObject\MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(static::once())
            ->method('execute')
            ->with(PaymentMethodInterface::CAPTURE, $capturePaymentTransaction)
            ->will($responseValue);

        $this->paymentMethodProvider
            ->method('hasPaymentMethod')
            ->with($authorizationPaymentTransaction->getPaymentMethod())
            ->willReturn(true);

        $this->paymentMethodProvider
            ->method('getPaymentMethod')
            ->with($authorizationPaymentTransaction->getPaymentMethod())
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider
            ->expects(static::exactly(2))
            ->method('savePaymentTransaction')
            ->withConsecutive(
                [$capturePaymentTransaction],
                [$authorizationPaymentTransaction]
            );

        $this->contextAccessor
            ->expects(static::once())
            ->method('setValue')
            ->with($context, $options['attribute'], $expected);

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('testPaymentMethodType');

        return [
            'default' => [
                'data' => [
                    'capturePaymentTransaction' => $paymentTransaction
                        ->setAction(PaymentMethodInterface::CAPTURE),
                    'options' => [
                        'paymentTransaction' => $paymentTransaction,
                        'attribute' => new PropertyPath('test'),
                        'transactionOptions' => [
                            'testOption' => 'testOption',
                        ],
                    ],
                    'response' => ['testResponse' => 'testResponse'],
                ],
                'expected' => [
                    'transaction' => null,
                    'successful' => false,
                    'message' => 'oro.payment.message.error',
                    'testOption' => 'testOption',
                    'testResponse' => 'testResponse',
                ],
            ],
            'throw exception' => [
                'data' => [
                    'capturePaymentTransaction' => $paymentTransaction
                        ->setAction(PaymentMethodInterface::CAPTURE),
                    'options' => [
                        'paymentTransaction' => $paymentTransaction,
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
     * @param array $options
     *
     * @dataProvider executeWrongOptionsDataProvider
     */
    public function testExecuteWrongOptions($options)
    {
        $this->expectException(\Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException::class);
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('testPaymentMethodType');

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    /**
     * @return array
     */
    public function executeWrongOptionsDataProvider()
    {
        return [
            [['someOption' => 'someValue']],
            [['object' => 'someValue']],
            [['amount' => 'someAmount']],
            [['currency' => 'someCurrency']],
            [['paymentMethod' => 'somePaymentMethod']],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getAction()
    {
        return new PaymentTransactionCaptureAction(
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

    public function testUseSourcePaymentTransaction()
    {
        $context = [];
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setSuccessful(true)
            ->setActive(true);
        $options = [
            'paymentTransaction' => $paymentTransaction,
            'attribute' => new PropertyPath('test'),
            'transactionOptions' => [
                'testOption' => 'testOption',
            ],
        ];

        $paymentMethod = $this->createMock(PaymentMethodWithPostponedCaptureInterface::class);
        $paymentMethod->expects($this->once())->method('useSourcePaymentTransaction')->willReturn(true);
        $paymentMethod->expects(static::once())
            ->method('execute')
            ->with(PaymentMethodInterface::CAPTURE, $paymentTransaction)
            ->willReturn(['testResponse' => 'testResponse']);
        $this->paymentMethodProvider->method('hasPaymentMethod')->willReturn(true);
        $this->paymentMethodProvider->method('getPaymentMethod')->willReturn($paymentMethod);
        $this->paymentTransactionProvider
            ->expects($this->never())
            ->method('createPaymentTransactionByParentTransaction');
        $this->contextAccessor->method('getValue')->will($this->returnArgument(1));

        $this->contextAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with(
                $context,
                $options['attribute'],
                [
                    'transaction' => null,
                    'successful' => true,
                    'message' => null,
                    'testOption' => 'testOption',
                    'testResponse' => 'testResponse',
                ]
            );

        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
