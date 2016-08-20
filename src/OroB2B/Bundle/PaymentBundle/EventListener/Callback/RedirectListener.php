<?php

namespace Oro\Bundle\PaymentBundle\EventListener\Callback;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;

class RedirectListener
{
    const SUCCESS_URL_KEY = 'successUrl';
    const FAILURE_URL_KEY = 'failureUrl';
    const FAILED_SHIPPING_ADDRESS_URL_KEY = 'failedShippingAddressUrl';

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
        $this->handleEvent($event, self::SUCCESS_URL_KEY);
    }

    /**
     * @param CallbackErrorEvent $event
     */
    public function onError(CallbackErrorEvent $event)
    {
        $this->handleEvent($event, self::FAILURE_URL_KEY);
        $this->setErrorMessage('oro.payment.result.error');
    }

    /**
     * @param AbstractCallbackEvent $event
     * @param string $expectedOptionsKey
     */
    protected function handleEvent(AbstractCallbackEvent $event, $expectedOptionsKey)
    {
        $transaction = $event->getPaymentTransaction();
        if (!$transaction) {
            return;
        }

        $transactionOptions = $transaction->getTransactionOptions();

        if (!empty($transactionOptions[$expectedOptionsKey])) {
            $event->setResponse(new RedirectResponse($transactionOptions[$expectedOptionsKey]));
        }
    }

    /**
     * @param string $message
     */
    protected function setErrorMessage($message)
    {
        $flashBag = $this->session->getFlashBag();

        if (!$flashBag->has('error')) {
            $flashBag->add('error', $message);
        }
    }
}
