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
        $paymentMethodConfig = $this->getPaymentMethodConfig($paymentTransaction->getPaymentMethod());

        $activationRequest = $this->requestMapper->createRequestFromOrder($order, $paymentMethodConfig, []);
        $activationResponse = $this->gateway->activate(
            $activationRequest,
            $paymentMethodConfig
        );

        $paymentTransaction = $this
            ->responseMapper
            ->mapResponseToPaymentTransaction($paymentTransaction, $activationResponse);

        return ['success' => $paymentTransaction->isSuccessful()];
    }
}
