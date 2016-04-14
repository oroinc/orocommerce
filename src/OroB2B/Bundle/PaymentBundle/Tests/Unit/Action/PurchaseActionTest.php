<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\Action\PurchaseAction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class PurchaseActionTest extends AbstractActionTest
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

        /** @var PaymentTransaction|\PHPUnit_Framework_MockObject_MockObject $capturePaymentTransaction */
        $capturePaymentTransaction = new PaymentTransaction();
        $capturePaymentTransaction
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
            ->with($data['options']['paymentMethod'])
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider
            ->expects($this->exactly(2))
            ->method('savePaymentTransaction');

        $this->router
            ->expects($this->any())
            ->method('generate')
            ->will($this->returnArgument(0));

        $this->contextAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with($context, $data['options']['attribute'], $expected);

        $this->action->execute($context);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'usual_case' => [
                'data' => [
                    'paymentTransaction' => new PaymentTransaction(),
                    'options' => [
                        'object' => new \stdClass(),
                        'amount' => 100.0,
                        'currency' => 'USD',
                        'attribute' => new PropertyPath('test'),
                        'paymentMethod' => 'testPaymentMethod',
                        'transactionOptions' => [
                            'testOption' => 'testOption'
                        ],
                    ],
                    'testPaymentMethodType' => 'testPaymentMethodType',
                    'testEntityIdentifier' => 10,
                    'response' => ['testResponse' => 'testResponse'],
                ],
                'expected' => [
                    'paymentMethod' => 'testPaymentMethod',
                    'errorUrl' => 'orob2b_payment_callback_error',
                    'returnUrl' => 'orob2b_payment_callback_return',
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
        return new PurchaseAction(
            $this->contextAccessor,
            $this->paymentMethodRegistry,
            $this->paymentTransactionProvider,
            $this->router
        );
    }
}
