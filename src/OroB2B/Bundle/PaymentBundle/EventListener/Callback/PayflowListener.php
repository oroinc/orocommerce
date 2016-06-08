<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener\Callback;

use Symfony\Component\HttpFoundation\Session\Session;

use OroB2B\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\Response;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseStatusMap;

class PayflowListener
{
    /** @var Session */
    protected $session;

    /** @var PaymentMethodRegistry */
    protected $paymentMethodRegistry;

    /**
     * @param Session $session
     * @param PaymentMethodRegistry $paymentMethodRegistry
     */
    public function __construct(Session $session, PaymentMethodRegistry $paymentMethodRegistry)
    {
        $this->session = $session;
        $this->paymentMethodRegistry = $paymentMethodRegistry;
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
    public function onNotify(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction || $paymentTransaction->getReference()) {
            return;
        }

        $data = $event->getData();
        $originAction = $paymentTransaction->getAction();

        $paymentTransaction
            ->setAction(PayflowGateway::COMPLETE)
            ->setResponse(array_replace($paymentTransaction->getResponse(), $data));

        try {
            $this->paymentMethodRegistry
                ->getPaymentMethod($paymentTransaction->getPaymentMethod())
                ->execute($paymentTransaction);
            $event->markSuccessful();
        } catch (\InvalidArgumentException $e) {
        }

        $paymentTransaction->setAction($originAction);
    }
}
