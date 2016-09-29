<?php

namespace Oro\Bundle\InfinitePayBundle\Action;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class Capture extends ActionAbstract
{
    /**
     * @param PaymentTransaction $paymentTransaction
     * @param Order              $order
     *
     * @return array
     */
    public function execute(PaymentTransaction $paymentTransaction, Order $order)
    {
        $captureRequest = $this->requestMapper->createRequestFromOrder($order, []);
        $captureResponse = $this->gateway->capture($captureRequest);

        $paymentTransaction = $this->responseMapper->mapResponseToPaymentTransaction(
            $paymentTransaction,
            $captureResponse
        );

        return ['success' => $paymentTransaction->isActive()];
    }
}
