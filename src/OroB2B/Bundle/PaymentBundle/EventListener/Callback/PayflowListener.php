<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener\Callback;

use OroB2B\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\Response;

class PayflowListener
{
    /**
     * @param AbstractCallbackEvent $event
     */
    public function onCallback(AbstractCallbackEvent $event)
    {
        $eventData = $event->getData();
        $response = new Response($eventData);

        $paymentTransaction = $event->getPaymentTransaction();
        $paymentTransactionData = $paymentTransaction->getData();

        $keys = [Option\SecureToken::SECURETOKEN, Option\SecureTokenIdentifier::SECURETOKENID];
        $keys = array_flip($keys);
        $dataToken = array_intersect_key($eventData, $keys);
        $transactionDataToken = array_intersect_key($paymentTransactionData, $keys);

        if (!$dataToken || !$transactionDataToken) {
            return;
        }

        if ($dataToken != $transactionDataToken) {
            return;
        }

        $paymentTransaction
            ->setState($response->getState())
            ->setReference($response->getReference())
            ->setData($eventData + $paymentTransactionData);
    }
}
