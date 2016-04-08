<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener\Callback;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

use OroB2B\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;

class RedirectListener
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
     * @param CallbackReturnEvent $event
     */
    public function onReturn(CallbackReturnEvent $event)
    {
        $this->handleEvent($event, 'successUrl');

        $event
            ->getPaymentTransaction()
            ->setActive(false)
            ->setSuccessful(true);
    }

    /**
     * @param CallbackErrorEvent $event
     */
    public function onError(CallbackErrorEvent $event)
    {
        $this->handleEvent($event, 'errorUrl');

        $event
            ->getPaymentTransaction()
            ->setActive(false)
            ->setSuccessful(false);

        if (!$this->session->has('error')) {
            $this->session->getFlashBag()->add('error', 'orob2b.payment.result.error');
        }
    }

    /**
     * @param AbstractCallbackEvent $event
     * @param string $expectedOptionsKey
     */
    protected function handleEvent(AbstractCallbackEvent $event, $expectedOptionsKey)
    {
        $transaction = $event->getPaymentTransaction();
        $transactionOptions = $transaction->getTransactionOptions();

        if (!empty($transactionOptions[$expectedOptionsKey])) {
            $event->setResponse(new RedirectResponse($transactionOptions[$expectedOptionsKey]));
        }
    }
}
