<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\PaymentBundle\Action\PaymentTransactionCaptureAction;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class PaymentTransactionCaptureActionTest extends AbstractActionTest
{
    /**
     * @dataProvider executeDataProvider
     *
     * @param array $data
     * @param array $expected
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

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(static::once())
            ->method('execute')
            ->with(PaymentMethodInterface::CAPTURE, $capturePaymentTransaction)
            ->will($responseValue);

        $paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);

        $paymentMethodProvider
            ->expects(static::once())
            ->method('hasPaymentMethod')
            ->with($authorizationPaymentTransaction->getPaymentMethod())
            ->willReturn(true);

        $paymentMethodProvider
            ->expects(static::once())
            ->method('getPaymentMethod')
            ->with($authorizationPaymentTransaction->getPaymentMethod())
            ->willReturn($paymentMethod);

        $this->paymentMethodProvidersRegistry
            ->expects(static::once())
            ->method('getPaymentMethodProviders')
            ->willReturn([$paymentMethodProvider]);

        $this->paymentTransactionProvider
            ->expects(static::exactly(2))
            ->method('savePaymentTransaction')
            ->withConsecutive(
                $capturePaymentTransaction,
                $authorizationPaymentTransaction
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
                        ->setAction(PaymentMethodInterface::CAPTURE)
                        ->setEntityIdentifier(10),
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
                    'transaction' => 10,
                    'successful' => false,
                    'message' => null,
                    'testOption' => 'testOption',
                    'testResponse' => 'testResponse',
                ],
            ],
            'throw exception' => [
                'data' => [
                    'capturePaymentTransaction' => $paymentTransaction
                        ->setAction(PaymentMethodInterface::CAPTURE)
                        ->setEntityIdentifier(10),
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
                    'transaction' => 10,
                    'successful' => false,
                    'message' => null,
                    'testOption' => 'testOption',
                ],
            ],
        ];
    }

    /**
     * @param array $options
     *
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     *
     * @dataProvider executeWrongOptionsDataProvider
     */
    public function testExecuteWrongOptions($options)
    {
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
            $this->paymentMethodProvidersRegistry,
            $this->paymentTransactionProvider,
            $this->router
        );
    }
}
