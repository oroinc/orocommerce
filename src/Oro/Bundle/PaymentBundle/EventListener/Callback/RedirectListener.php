<?php

namespace Oro\Bundle\PaymentBundle\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Oro\Bundle\PaymentBundle\Provider\PaymentResultMessageProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Redirect listener class.
 */
class RedirectListener
{
    public const SUCCESS_URL_KEY = 'successUrl';
    public const FAILURE_URL_KEY = 'failureUrl';

    public function __construct(
        protected RequestStack $requestStack,
        protected PaymentResultMessageProviderInterface $messageProvider
    ) {
    }

    public function onReturn(CallbackReturnEvent $event)
    {
        $this->handleEvent($event, self::SUCCESS_URL_KEY);
    }

    public function onError(CallbackErrorEvent $event)
    {
        $this->handleEvent($event, self::FAILURE_URL_KEY);
        $this->setErrorMessage($this->messageProvider->getErrorMessage($event->getPaymentTransaction()));
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
        $flashBag = $this->requestStack->getSession()->getFlashBag();

        if (!$flashBag->has('error')) {
            $flashBag->add('error', $message);
        }
    }
}
