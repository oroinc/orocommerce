<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\PaymentBundle\Action\CaptureAction;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProviderInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class CaptureActionTest extends AbstractActionTest
{
    const PAYMENT_METHOD = 'testPaymentMethodType';

    public function testExecuteWithoutTransaction()
    {
        $options = [
            'object' => new \stdClass(),
            'amount' => 100.0,
            'currency' => 'USD',
            'attribute' => new PropertyPath('test'),
            'paymentMethod' => self::PAYMENT_METHOD,
            'transactionOptions' => [],
        ];

        $this->action->initialize($options);

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('getActiveAuthorizePaymentTransaction')
            ->willReturn(null);

        $this->paymentTransactionProvider
            ->expects($this->never())
            ->method('createPaymentTransaction');

        $this->action->execute([]);
    }

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
            ->with($options['object'], $options['amount'], $options['currency'], $options['paymentMethod'])
            ->willReturn($paymentTransaction);

        $responseValue = $this->returnValue($data['response']);

        if ($data['response'] instanceof \Exception) {
            $responseValue = $this->throwException($data['response']);
        }

        $capturePaymentTransaction = new PaymentTransaction();
        $capturePaymentTransaction
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setPaymentMethod($options['paymentMethod'])
            ->setEntityIdentifier($data['testEntityIdentifier']);

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects($this->once())
            ->method('execute')
            ->with(PaymentMethodInterface::CAPTURE, $capturePaymentTransaction)
            ->will($responseValue);

        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('createPaymentTransaction')
            ->with($options['paymentMethod'], PaymentMethodInterface::CAPTURE, $options['object'])
            ->willReturn($capturePaymentTransaction);

        $paymentMethodProvider = $this->getMockBuilder(PaymentMethodProviderInterface::class)->getMock();

        $paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with($options['paymentMethod'])
            ->willReturn(true);
        
        $paymentMethodProvider
            ->expects($this->once())
            ->method('getPaymentMethod')
            ->with($options['paymentMethod'])
            ->willReturn($paymentMethod);

        $this->paymentMethodProvidersRegistry
            ->expects($this->once())
            ->method('getPaymentMethodProviders')
            ->willReturn([$paymentMethodProvider]);

        $this->paymentTransactionProvider
            ->expects($this->exactly(2))
            ->method('savePaymentTransaction')
            ->withConsecutive(
                $paymentTransaction,
                $capturePaymentTransaction,
                $paymentTransaction
            );

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
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod(self::PAYMENT_METHOD);
        return [
            'default' => [
                'data' => [
                    'paymentTransaction' => $paymentTransaction,
                    'options' => [
                        'object' => new \stdClass(),
                        'amount' => 100.0,
                        'currency' => 'USD',
                        'attribute' => new PropertyPath('test'),
                        'paymentMethod' => self::PAYMENT_METHOD,
                        'transactionOptions' => [
                            'testOption' => 'testOption'
                        ],
                    ],
                    'testEntityIdentifier' => 10,
                    'response' => ['testResponse' => 'testResponse'],
                ],
                'expected' => [
                    'transaction' => 10,
                    'successful' => false,
                    'message' => null,
                    'testResponse' => 'testResponse',
                    'testOption' => 'testOption',
                ]
            ],
            'throw exception' => [
                'data' => [
                    'paymentTransaction' => $paymentTransaction,
                    'options' => [
                        'object' => new \stdClass(),
                        'amount' => 100.0,
                        'currency' => 'USD',
                        'attribute' => new PropertyPath('test'),
                        'paymentMethod' => self::PAYMENT_METHOD,
                        'transactionOptions' => [
                            'testOption' => 'testOption'
                        ],
                    ],
                    'testEntityIdentifier' => 10,
                    'response' => new \Exception(),
                ],
                'expected' => [
                    'transaction' => 10,
                    'successful' => false,
                    'message' => null,
                    'testOption' => 'testOption',
                ]
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAction()
    {
        return new CaptureAction(
            $this->contextAccessor,
            $this->paymentMethodProvidersRegistry,
            $this->paymentTransactionProvider,
            $this->router
        );
    }
}
