<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\PaymentTransaction\Action\PaymentTransactionInvoiceAction;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\PropertyAccess\PropertyPath;

class PaymentTransactionInvoiceActionTest extends AbstractActionTest
{
    use EntityTrait;

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
        $invoicePaymentTransaction = $data['invoicePaymentTransaction'];
        $shipmentPaymentTransaction = $data['shipmentPaymentTransaction'];
        $options = $data['options'];
        $context = [];

        $this->contextAccessor
            ->expects(static::any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->paymentTransactionProvider
            ->expects(static::atLeastOnce())
            ->method('createPaymentTransactionByParentTransaction')
            ->willReturnMap([
                [ApruvePaymentMethod::INVOICE, $authorizationPaymentTransaction, $invoicePaymentTransaction],
                [ApruvePaymentMethod::SHIPMENT, $invoicePaymentTransaction, $shipmentPaymentTransaction],
            ]);

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod
            ->expects(static::atLeastOnce())
            ->method('execute')
            ->willReturnMap([
                [ApruvePaymentMethod::INVOICE, $invoicePaymentTransaction, $data['invoiceResponse']],
                [ApruvePaymentMethod::SHIPMENT, $shipmentPaymentTransaction, $data['shipmentResponse']],
            ]);

        $paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);

        $paymentMethodProvider
            ->expects(static::atLeastOnce())
            ->method('hasPaymentMethod')
            ->with($authorizationPaymentTransaction->getPaymentMethod())
            ->willReturn(true);

        $paymentMethodProvider
            ->expects(static::atLeastOnce())
            ->method('getPaymentMethod')
            ->with($authorizationPaymentTransaction->getPaymentMethod())
            ->willReturn($paymentMethod);

        $this->paymentMethodProvidersRegistry
            ->expects(static::atLeastOnce())
            ->method('getPaymentMethodProviders')
            ->willReturn([$paymentMethodProvider]);

        $this->paymentTransactionProvider
            ->expects(static::atLeastOnce())
            ->method('savePaymentTransaction')
            ->withConsecutive(
                $invoicePaymentTransaction,
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
        return [
            'successful' => [
                'data' => [
                    'invoicePaymentTransaction' => $this->createPaymentTransaction(10)
                        ->setAction(ApruvePaymentMethod::INVOICE)
                        ->setEntityIdentifier(10)
                        ->setSuccessful(true),
                    'shipmentPaymentTransaction' => $this->createPaymentTransaction(20)
                        ->setAction(ApruvePaymentMethod::SHIPMENT)
                        ->setEntityIdentifier(20)
                        ->setSuccessful(true),
                    'options' => [
                        'paymentTransaction' => $this->createPaymentTransaction(1),
                        'attribute' => new PropertyPath('test'),
                        'transactionOptions' => [
                            'testOption' => 'testOption',
                        ],
                    ],
                    'invoiceResponse' => ['testInvoiceResponse' => 'testResponse'],
                    'shipmentResponse' => ['testShipmentResponse' => 'testResponse'],
                ],
                'expected' => [
                    'transaction' => 20,
                    'invoiceTransaction' => 10,
                    'successful' => true,
                    'message' => null,
                    'testOption' => 'testOption',
                    'testInvoiceResponse' => 'testResponse',
                    'testShipmentResponse' => 'testResponse',
                ],
            ],
            'invoice is not successful' => [
                'data' => [
                    'invoicePaymentTransaction' => $this->createPaymentTransaction(10)
                                                        ->setAction(ApruvePaymentMethod::INVOICE)
                                                        ->setEntityIdentifier(10)
                                                        ->setSuccessful(false),
                    'shipmentPaymentTransaction' => null,
                    'options' => [
                        'paymentTransaction' => $this->createPaymentTransaction(1),
                        'attribute' => new PropertyPath('test'),
                        'transactionOptions' => [
                            'testOption' => 'testOption',
                        ],
                    ],
                    'invoiceResponse' => ['testInvoiceResponse' => 'testResponse'],
                    'shipmentResponse' => null,
                ],
                'expected' => [
                    'transaction' => 10,
                    'successful' => false,
                    'message' => null,
                    'testOption' => 'testOption',
                    'testInvoiceResponse' => 'testResponse',
                ],
            ],
            'shipment is not successful' => [
                'data' => [
                    'invoicePaymentTransaction' => $this->createPaymentTransaction(10)
                                                        ->setAction(ApruvePaymentMethod::INVOICE)
                                                        ->setEntityIdentifier(10)
                                                        ->setSuccessful(true),
                    'shipmentPaymentTransaction' => $this->createPaymentTransaction(20)
                                                         ->setAction(ApruvePaymentMethod::SHIPMENT)
                                                         ->setEntityIdentifier(20)
                                                         ->setSuccessful(false),
                    'options' => [
                        'paymentTransaction' => $this->createPaymentTransaction(1),
                        'attribute' => new PropertyPath('test'),
                        'transactionOptions' => [
                            'testOption' => 'testOption',
                        ],
                    ],
                    'invoiceResponse' => ['testInvoiceResponse' => 'testResponse'],
                    'shipmentResponse' => ['testShipmentResponse' => 'testResponse'],
                ],
                'expected' => [
                    'transaction' => 20,
                    'invoiceTransaction' => 10,
                    'successful' => false,
                    'message' => null,
                    'testOption' => 'testOption',
                    'testInvoiceResponse' => 'testResponse',
                    'testShipmentResponse' => 'testResponse',
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
        return new PaymentTransactionInvoiceAction(
            $this->contextAccessor,
            $this->paymentMethodProvidersRegistry,
            $this->paymentTransactionProvider,
            $this->router
        );
    }

    /**
     * @param int $id
     *
     * @return PaymentTransaction
     */
    private function createPaymentTransaction($id)
    {
        $properties = ['id' => $id, 'paymentMethod' => 'testPaymentMethodType'];

        return $this->getEntity(PaymentTransaction::class, $properties);
    }
}
