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
        $paymentMethodConfig = $this->getPaymentMethodConfig($paymentTransaction->getPaymentMethod());

        $captureRequest = $this->requestMapper->createRequestFromOrder($order, $paymentMethodConfig, []);
        $captureResponse = $this->gateway->capture(
            $captureRequest,
            $paymentMethodConfig
        );

        $paymentTransaction = $this->responseMapper->mapResponseToPaymentTransaction(
            $paymentTransaction,
            $captureResponse
        );

        return ['success' => $paymentTransaction->isActive()];
    }
}
