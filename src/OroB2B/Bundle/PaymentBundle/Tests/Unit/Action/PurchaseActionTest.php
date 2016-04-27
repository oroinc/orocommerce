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
        $options = $data['options'];

        $exceptionWillThrow = false;
        $responseValue = $this->returnValue($data['response']);

        if ($data['response'] instanceof \Exception) {
            $responseValue = $this->throwException($data['response']);
            $exceptionWillThrow = true;
        }

        $this->action->initialize($options);

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $paymentTransaction = new PaymentTransaction();

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->once())
            ->method('execute')
            ->with($paymentTransaction)
            ->will($responseValue);

        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('createPaymentTransaction')
            ->with($options['paymentMethod'], PaymentMethodInterface::PURCHASE, $options['object'])
            ->willReturn($paymentTransaction);

        $this->paymentMethodRegistry
            ->expects($this->once())
            ->method('getPaymentMethod')
            ->with($options['paymentMethod'])
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider
            ->expects($this->exactly($exceptionWillThrow ? 1 : 2))
            ->method('savePaymentTransaction')
            ->with($paymentTransaction)
            ->willReturnCallback(
                function (PaymentTransaction $paymentTransaction) use ($options) {
                    $this->assertEquals($options['amount'], $paymentTransaction->getAmount());
                    $this->assertEquals($options['currency'], $paymentTransaction->getCurrency());
                    if (!empty($options['transactionOptions'])) {
                        $this->assertEquals(
                            $options['transactionOptions'],
                            $paymentTransaction->getTransactionOptions()
                        );
                    }
                }
            );

        $this->router
            ->expects($this->any())
            ->method('generate')
            ->withConsecutive(
                [
                    'orob2b_payment_callback_error',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                        'accessToken' => $paymentTransaction->getAccessToken(),
                    ],
                    true
                ],
                [
                    'orob2b_payment_callback_return',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                        'accessToken' => $paymentTransaction->getAccessToken(),
                    ],
                    true
                ]
            )
            ->will($this->returnArgument(0));

        $this->contextAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with($context, $options['attribute'], $expected);

        $this->action->execute($context);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'default' => [
                'data' => [
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
                    'response' => ['testResponse' => 'testResponse'],
                ],
                'expected' => [
                    'paymentMethod' => 'testPaymentMethod',
                    'errorUrl' => 'orob2b_payment_callback_error',
                    'returnUrl' => 'orob2b_payment_callback_return',
                    'testResponse' => 'testResponse',
                ]
            ],
            'throw exception' => [
                'data' => [
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
                    'response' => new \Exception(),
                ],
                'expected' => [
                    'paymentMethod' => 'testPaymentMethod',
                    'errorUrl' => 'orob2b_payment_callback_error',
                    'returnUrl' => 'orob2b_payment_callback_return',
                ]
            ],
        ];
    }

    /**
     * {@inheritdoc}
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
