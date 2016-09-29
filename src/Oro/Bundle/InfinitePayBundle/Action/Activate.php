<?php

namespace Oro\Bundle\InfinitePayBundle\Action;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class Activate extends ActionAbstract
{
    /**
     * @param PaymentTransaction $paymentTransaction
     * @param Order              $order
     *
     * @return array
     */
    public function execute(PaymentTransaction $paymentTransaction, Order $order)
    {
        $activationRequest = $this->requestMapper->createRequestFromOrder($order, []);
        $activationResponse = $this->gateway->activate($activationRequest);

        $paymentTransaction = $this
            ->responseMapper
            ->mapResponseToPaymentTransaction($paymentTransaction, $activationResponse);

        return ['success' => $paymentTransaction->isSuccessful()];
    }
}
