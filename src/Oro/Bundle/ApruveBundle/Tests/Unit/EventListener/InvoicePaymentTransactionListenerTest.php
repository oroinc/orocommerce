<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\EventListener\Callback;

use Oro\Bundle\ApruveBundle\EventListener\InvoicePaymentTransactionListener;
use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class InvoicePaymentTransactionListenerTest extends \PHPUnit_Framework_TestCase
{
    const PAYMENT_METHOD = 'payment_method';

    /**
     * @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMethod;

    /**
     * @var InvoicePaymentTransactionListener
     */
    private $listener;

    /**
     * @var PaymentMethodProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMethodProvider;

    /**
     * @var PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTransactionProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);

        $this->listener = new InvoicePaymentTransactionListener(
            $this->paymentMethodProvider,
            $this->paymentTransactionProvider
        );
    }

    public function testOnTransactionComplete()
    {
        $invoicePaymentTransaction = $this->createInvoicePaymentTransaction();

        $shipmentPaymentTransaction = new PaymentTransaction();
        $shipmentPaymentTransaction
            ->setAction(ApruvePaymentMethod::SHIPMENT);

        $this->paymentMethodProvider
            ->expects(static::once())
            ->method('hasPaymentMethod')
            ->with(self::PAYMENT_METHOD)
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects(static::once())
            ->method('getPaymentMethod')
            ->with(self::PAYMENT_METHOD)
            ->willReturn($this->paymentMethod);

        $this->paymentTransactionProvider
            ->expects(static::once())
            ->method('createPaymentTransactionByParentTransaction')
            ->with(ApruvePaymentMethod::SHIPMENT, $invoicePaymentTransaction)
            ->willReturn($shipmentPaymentTransaction);

        $this->paymentMethod
            ->expects(static::once())
            ->method('execute')
            ->with(ApruvePaymentMethod::SHIPMENT, $shipmentPaymentTransaction);

        $this->paymentTransactionProvider
            ->expects(static::once())
            ->method('savePaymentTransaction')
            ->with($shipmentPaymentTransaction);

        $event = new TransactionCompleteEvent($invoicePaymentTransaction);

        $this->listener->onTransactionComplete($event);
    }

    public function testOnTransactionCompleteIfNotSupportedAction()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction('unsupported_action')
            ->setPaymentMethod(self::PAYMENT_METHOD);

        $this->paymentMethodProvider
            ->expects(static::never())
            ->method('hasPaymentMethod');

        $this->paymentMethodProvider
            ->expects(static::never())
            ->method('getPaymentMethod');

        $event = new TransactionCompleteEvent($paymentTransaction);

        $this->listener->onTransactionComplete($event);
    }

    public function testOnTransactionCompleteIfNotSupportedPaymentMethod()
    {
        $invoicePaymentTransaction = $this->createInvoicePaymentTransaction();

        $this->paymentMethodProvider
            ->expects(static::once())
            ->method('hasPaymentMethod')
            ->with(self::PAYMENT_METHOD)
            ->willReturn(false);

        $this->paymentMethodProvider
            ->expects(static::never())
            ->method('getPaymentMethod');

        $event = new TransactionCompleteEvent($invoicePaymentTransaction);

        $this->listener->onTransactionComplete($event);
    }

    /**
     * @return PaymentTransaction
     */
    private function createInvoicePaymentTransaction()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction(ApruvePaymentMethod::INVOICE)
            ->setPaymentMethod(self::PAYMENT_METHOD);

        return $paymentTransaction;
    }
}
