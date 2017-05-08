<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\PaymentTransaction\Action\PaymentTransactionInvoiceAction;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class PaymentTransactionInvoiceActionTest extends AbstractActionTest
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
        $invoicePaymentTransaction = $data['invoicePaymentTransaction'];
        $shipmentPaymentTransaction = $data['shipmentPaymentTransaction'];
        $options = $data['options'];
        $context = [];

        $this->contextAccessor
            ->expects(static::any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->paymentTransactionProvider
            ->expects(static::exactly(2))
            ->method('createPaymentTransactionByParentTransaction')
            ->willReturnMap([
                [ApruvePaymentMethod::INVOICE, $authorizationPaymentTransaction, $invoicePaymentTransaction],
                [ApruvePaymentMethod::SHIPMENT, $invoicePaymentTransaction, $shipmentPaymentTransaction],
            ]);

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(static::exactly(2))
            ->method('execute')
            ->willReturnMap([
                [ApruvePaymentMethod::INVOICE, $invoicePaymentTransaction, $data['invoiceResponse']],
                [ApruvePaymentMethod::SHIPMENT, $shipmentPaymentTransaction, $data['shipmentResponse']],
            ]);

        $paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);

        $paymentMethodProvider
            ->expects(static::exactly(2))
            ->method('hasPaymentMethod')
            ->with($authorizationPaymentTransaction->getPaymentMethod())
            ->willReturn(true);

        $paymentMethodProvider
            ->expects(static::exactly(2))
            ->method('getPaymentMethod')
            ->with($authorizationPaymentTransaction->getPaymentMethod())
            ->willReturn($paymentMethod);

        $this->paymentMethodProvidersRegistry
            ->expects(static::exactly(2))
            ->method('getPaymentMethodProviders')
            ->willReturn([$paymentMethodProvider]);

        $this->paymentTransactionProvider
            ->expects(static::exactly(4))
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
            'default' => [
                'data' => [
                    'invoicePaymentTransaction' => $this->createPaymentTransaction()
                        ->setAction(ApruvePaymentMethod::INVOICE)
                        ->setEntityIdentifier(10),
                    'shipmentPaymentTransaction' => $this->createPaymentTransaction()
                        ->setAction(ApruvePaymentMethod::SHIPMENT)
                        ->setEntityIdentifier(20),
                    'options' => [
                        'paymentTransaction' => $this->createPaymentTransaction(),
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
                    'shipmentTransaction' => 20,
                    'successful' => false,
                    'message' => null,
                    'testOption' => 'testOption',
                    'testInvoiceResponse' => 'testResponse',
                    'testShipmentResponse' => 'testResponse',
                ],
            ]
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
     * @return PaymentTransaction
     */
    private function createPaymentTransaction()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('testPaymentMethodType');

        return $paymentTransaction;
    }
}
