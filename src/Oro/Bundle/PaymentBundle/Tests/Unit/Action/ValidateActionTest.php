<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\PaymentBundle\Action\ValidateAction;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;

class ValidateActionTest extends AbstractActionTest
{
    const PAYMENT_METHOD = 'testPaymentMethod';

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

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->action->initialize($options);

        /** @var PaymentMethodInterface|\PHPUnit\Framework\MockObject\MockObject $paymentMethod */
        $paymentMethod = $this->createMock('Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface');

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction(PaymentMethodInterface::VALIDATE)
            ->setPaymentMethod($options['paymentMethod']);

        $paymentMethod->expects($this->once())
            ->method('execute')
            ->with($paymentTransaction->getAction(), $paymentTransaction)
            ->will($responseValue);

        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('createPaymentTransaction')
            ->with($options['paymentMethod'], PaymentMethodInterface::VALIDATE, $options['object'])
            ->willReturn($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->atLeastOnce())
            ->method('hasPaymentMethod')
            ->with($options['paymentMethod'])
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects($this->atLeastOnce())
            ->method('getPaymentMethod')
            ->with($options['paymentMethod'])
            ->willReturn($paymentMethod);

        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('savePaymentTransaction')
            ->with($paymentTransaction)
            ->willReturnCallback(
                function (PaymentTransaction $paymentTransaction) use ($options) {
                    if (!empty($options['transactionOptions'])) {
                        $this->assertEquals(
                            $options['transactionOptions'],
                            $paymentTransaction->getTransactionOptions()
                        );
                    }
                }
            );

        $this->contextAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with($context, $options['attribute'], $expected);

        $this->router
            ->expects($this->exactly(2))
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
            ->will($this->returnArgument(0));

        $this->action->execute($context);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
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

    /**
     * {@inheritdoc}
     */
    protected function getAction()
    {
        return new ValidateAction(
            $this->contextAccessor,
            $this->paymentMethodProvider,
            $this->paymentTransactionProvider,
            $this->router
        );
    }
}
