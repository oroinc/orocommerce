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
        $context = [];
        $this->action->initialize($data['options']);

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('getActiveAuthorizePaymentTransaction')
            ->willReturn($data['paymentTransaction']);

        if ($data['paymentTransaction']) {

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
                ->willReturn($data['response']);

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
                ->expects($this->exactly(3))
                ->method('savePaymentTransaction');

            $this->contextAccessor
                ->expects($this->once())
                ->method('setValue')
                ->with($context, $data['options']['attribute'], $expected);

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
                    ],
                    'testPaymentMethodType' => 'testPaymentMethodType',
                    'testEntityIdentifier' => 10,
                ],
                'expected' => [
                ]
            ],

            'usual_case' => [
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
