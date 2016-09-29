<?php

namespace Oro\Bundle\InfinitePayBundle\Action;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class ApplyTransaction extends ActionAbstract
{
    /**
     * @param PaymentTransaction $paymentTransaction
     * @param Order              $order
     *
     * @return array
     */
    public function execute(PaymentTransaction $paymentTransaction, Order $order)
    {
        $refNo = $paymentTransaction->getSourcePaymentTransaction()->getReference();
        $applyTransactionRequest = $this->requestMapper->createRequestFromOrder($order, ['ref_no' => $refNo]);

        $response = $this->gateway->applyTransaction($applyTransactionRequest);

        $paymentTransaction = $this->responseMapper->mapResponseToPaymentTransaction($paymentTransaction, $response);

        return ['success' => $paymentTransaction->isSuccessful()];
    }
}
