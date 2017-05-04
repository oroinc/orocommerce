<?php

namespace Oro\Bundle\ApruveBundle\EventListener;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class InvoicePaymentTransactionListener
{
    /**
     * @var PaymentMethodProviderInterface
     */
    private $apruvePaymentMethodProvider;

    /**
     * @var PaymentTransactionProvider
     */
    private $paymentTransactionProvider;

    /**
     * @param PaymentMethodProviderInterface $apruvePaymentMethodProvider
     * @param PaymentTransactionProvider     $paymentTransactionProvider
     */
    public function __construct(
        PaymentMethodProviderInterface $apruvePaymentMethodProvider,
        PaymentTransactionProvider $paymentTransactionProvider
    ) {
        $this->apruvePaymentMethodProvider = $apruvePaymentMethodProvider;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * @param TransactionCompleteEvent $event
     */
    public function onTransactionComplete(TransactionCompleteEvent $event)
    {
        $transaction = $event->getTransaction();

        if (!$this->isSupported($transaction)) {
            return;
        }

        $paymentMethod = $this->apruvePaymentMethodProvider
            ->getPaymentMethod($transaction->getPaymentMethod());
        $shipmentTransaction = $this->paymentTransactionProvider
            ->createPaymentTransactionByParentTransaction(ApruvePaymentMethod::SHIPMENT, $transaction);

        $paymentMethod->execute(ApruvePaymentMethod::SHIPMENT, $shipmentTransaction);

        $this->paymentTransactionProvider->savePaymentTransaction($shipmentTransaction);
    }

    /**
     * @param PaymentTransaction $transaction
     *
     * @return bool
     */
    private function isSupported(PaymentTransaction $transaction)
    {
        return $transaction->getAction() === ApruvePaymentMethod::INVOICE
            && $this->apruvePaymentMethodProvider->hasPaymentMethod($transaction->getPaymentMethod());
    }
}
