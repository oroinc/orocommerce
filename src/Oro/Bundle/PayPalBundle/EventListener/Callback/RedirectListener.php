<?php

namespace Oro\Bundle\PayPalBundle\EventListener\Callback;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseStatusMap;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response;

use OroB2B\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackErrorEvent;

class RedirectListener
{
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
     * @param CallbackErrorEvent $event
     */
    public function onError(CallbackErrorEvent $event)
    {
        if (!$this->checkResponse($event, ResponseStatusMap::FIELD_FORMAT_ERROR)) {
            return;
        }

        $this->handleEvent($event, self::FAILED_SHIPPING_ADDRESS_URL_KEY);
        $this->setErrorMessage('oro.paypal.result.incorrect_shipping_address_error');

        $event->stopPropagation();
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

    /**
     * @param AbstractCallbackEvent $event
     * @param string $responseCode
     * @return bool
     */
    protected function checkResponse(AbstractCallbackEvent $event, $responseCode)
    {
        $transaction = $event->getPaymentTransaction();
        if (!$transaction) {
            return false;
        }

        $response = new Response($transaction->getResponse());

        return $response->getResult() === $responseCode;
    }
}
