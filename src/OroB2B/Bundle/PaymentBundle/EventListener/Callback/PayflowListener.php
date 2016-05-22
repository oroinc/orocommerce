<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener\Callback;

use Symfony\Component\HttpFoundation\Session\Session;

use OroB2B\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\Response;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseStatusMap;

class PayflowListener
{
    /** @var Session */
    protected $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onError(AbstractCallbackEvent $event)
    {
        $this->onCallback($event);

        $eventData = $event->getData();
        $response = new Response($eventData);

        if (in_array($response->getResult(), [ResponseStatusMap::SECURE_TOKEN_EXPIRED], true)) {
            $this->session->getFlashBag()->set('warning', 'orob2b.payment.result.token_expired');
        }
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onCallback(AbstractCallbackEvent $event)
    {
        $eventData = $event->getData();
        $response = new Response($eventData);

        $paymentTransaction = $event->getPaymentTransaction();
        $paymentTransactionData = $paymentTransaction->getResponse();

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
            ->setReference($response->getReference())
            ->setResponse(array_replace($paymentTransactionData, $eventData));

        if (in_array(
            $paymentTransaction->getAction(),
            [PaymentMethodInterface::AUTHORIZE, PaymentMethodInterface::VALIDATE],
            true
        )) {
            $paymentTransaction
                ->setActive($response->isSuccessful())
                ->setSuccessful($response->isSuccessful());
        }
    }
}
