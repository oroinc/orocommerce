<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener\Callback;

use Symfony\Component\HttpFoundation\RedirectResponse;

use OroB2B\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;

class RedirectListener
{
    /**
     * @param CallbackReturnEvent $event
     */
    public function onReturn(CallbackReturnEvent $event)
    {
        $this->handleEvent($event, 'successUrl');
    }

    /**
     * @param CallbackErrorEvent $event
     */
    public function onError(CallbackErrorEvent $event)
    {
        $this->handleEvent($event, 'errorUrl');
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
