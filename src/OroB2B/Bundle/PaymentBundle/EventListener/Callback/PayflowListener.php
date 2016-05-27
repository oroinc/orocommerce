<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener\Callback;

use Symfony\Component\HttpFoundation\Session\Session;

use OroB2B\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
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
        $eventData = $event->getData();
        $response = new Response($eventData);

        if (in_array($response->getResult(), [ResponseStatusMap::SECURE_TOKEN_EXPIRED], true)) {
            $this->session->getFlashBag()->set('warning', 'orob2b.payment.result.token_expired');
        }
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onRetrieve(AbstractCallbackEvent $event)
    {
        $eventData = $event->getData();

        $criteria = $this->getCriteria($eventData);

        $event->setCriteria($criteria);
    }

    /**
     * @param array $eventData
     * @return array
     */
    protected function getCriteria(array $eventData)
    {
        $criteria = [];

        if (array_key_exists(Option\Optional::USER1, $eventData)) {
            $criteria['accessIdentifier'] = $eventData[Option\Optional::USER1];
        }

        if (array_key_exists(Option\Optional::USER2, $eventData)) {
            $criteria['accessToken'] = $eventData[Option\Optional::USER2];
        }

        return [];
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onNotify(AbstractCallbackEvent $event)
    {
        $eventData = $event->getData();
        $response = new Response($eventData);

        $paymentTransaction = $event->getPaymentTransaction();
        if (!$paymentTransaction) {
            return;
        }

        $paymentTransactionData = [
            'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
            'accessToken' => $paymentTransaction->getAccessToken(),
        ];

        if ($paymentTransactionData != $this->getCriteria($eventData)) {
            return;
        }

        $paymentTransaction
            ->setReference($response->getReference())
            ->setResponse(array_replace($paymentTransaction->getResponse(), $eventData))
            ->setActive($response->isSuccessful())
            ->setSuccessful($response->isSuccessful());
    }
}
