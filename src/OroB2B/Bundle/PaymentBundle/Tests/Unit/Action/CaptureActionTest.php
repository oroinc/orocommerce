<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\Action\CaptureAction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class CaptureActionTest extends AbstractActionTest
{
    /**
     * @dataProvider executeDataProvider
     * @param array $data
     * @param array $expected
     */
    public function testExecute(array $data, array $expected)
    {
        $paymentTransaction = $data['paymentTransaction'];
        $options = $data['options'];

        $context = [];

        $this->action->initialize($options);

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('getActiveAuthorizePaymentTransaction')
            ->willReturn($paymentTransaction);

        if ($paymentTransaction) {
            $exceptionWillThrow = false;
            $responseValue = $this->returnValue($data['response']);

            if ($data['response'] instanceof \Exception) {
                $responseValue = $this->throwException($data['response']);
                $exceptionWillThrow = true;
            }

            /** @var PaymentTransaction|\PHPUnit_Framework_MockObject_MockObject $capturePaymentTransaction */
            $capturePaymentTransaction = new PaymentTransaction();
            $capturePaymentTransaction
                ->setPaymentMethod($data['testPaymentMethodType'])
                ->setEntityIdentifier($data['testEntityIdentifier']);

            /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
            $paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
            $paymentMethod->expects($this->once())
                ->method('execute')
                ->with($capturePaymentTransaction)
                ->will($responseValue);

            $this->paymentTransactionProvider
                ->expects($this->once())
                ->method('createPaymentTransaction')
                ->willReturn($capturePaymentTransaction);

            $this->paymentMethodRegistry
                ->expects($this->once())
                ->method('getPaymentMethod')
                ->with($data['testPaymentMethodType'])
                ->willReturn($paymentMethod);

            $this->paymentTransactionProvider
                ->expects($this->exactly($exceptionWillThrow ? 2 : 3))
                ->method('savePaymentTransaction')
                ->withConsecutive(
                    $paymentTransaction,
                    $capturePaymentTransaction,
                    $paymentTransaction
                );

            $this->contextAccessor
                ->expects(!empty($options['attribute']) ? $this->once() : $this->never())
                ->method('setValue')
                ->with($context, $options['attribute'], $expected);

        } else {
            $this->paymentTransactionProvider
                ->expects($this->never())
                ->method('createPaymentTransaction');
        }

        $this->action->execute($context);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'empty_payment_transaction' => [
                'data' => [
                    'paymentTransaction' => null,
                    'options' => [
                        'object' => new \stdClass(),
                        'amount' => 100.0,
                        'currency' => 'USD',
                        'attribute' => new PropertyPath('test'),
                        'transactionOptions' => [],
                    ]
                ],
                'expected' => [
                ]
            ],
            'default' => [
                'data' => [
                    'paymentTransaction' => new PaymentTransaction(),
                    'options' => [
                        'object' => new \stdClass(),
                        'amount' => 100.0,
                        'currency' => 'USD',
                        'attribute' => new PropertyPath('test'),
                        'transactionOptions' => [
                            'testOption' => 'testOption'
                        ],
                    ],
                    'testPaymentMethodType' => 'testPaymentMethodType',
                    'testEntityIdentifier' => 10,
                    'response' => ['testResponse' => 'testResponse'],
                ],
                'expected' => [
                    'transaction' => 10,
                    'successful' => false,
                    'message' => null,
                    'testResponse' => 'testResponse',
                ]
            ],
            'throw exception' => [
                'data' => [
                    'paymentTransaction' => new PaymentTransaction(),
                    'options' => [
                        'object' => new \stdClass(),
                        'amount' => 100.0,
                        'currency' => 'USD',
                        'attribute' => new PropertyPath('test'),
                        'transactionOptions' => [
                            'testOption' => 'testOption'
                        ],
                    ],
                    'testPaymentMethodType' => 'testPaymentMethodType',
                    'testEntityIdentifier' => 10,
                    'response' => new \Exception(),
                ],
                'expected' => [
                    'transaction' => 10,
                    'successful' => false,
                    'message' => null,
                ]
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getAction()
    {
        return new CaptureAction(
            $this->contextAccessor,
            $this->paymentMethodRegistry,
            $this->paymentTransactionProvider,
            $this->router
        );
    }
}
