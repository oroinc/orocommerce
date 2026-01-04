<?php

namespace Oro\Bundle\PayPalBundle\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseStatusMap;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Listen payment error callbacks from payment and redirect to shipping address url in case when PayPal returns
 * that some problem with passed address data in request
 */
class RedirectListener
{
    public const FAILED_SHIPPING_ADDRESS_URL_KEY = 'failedShippingAddressUrl';

    public function __construct(
        protected RequestStack $requestStack,
        protected PaymentMethodProviderInterface $paymentMethodProvider
    ) {
    }

    public function onError(CallbackErrorEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction) {
            return;
        }

        if (false === $this->paymentMethodProvider->hasPaymentMethod($paymentTransaction->getPaymentMethod())) {
            return;
        }

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

    /**
     * @param AbstractCallbackEvent $event
     * @param string $responseCode
     * @return bool
     */
    protected function checkResponse(AbstractCallbackEvent $event, $responseCode)
    {
        $transaction = $event->getPaymentTransaction();
        $response = new Response($transaction->getResponse());

        return $response->getResult() === $responseCode &&
            strpos($response->getMessage(), 'Field format error: 10736') === 0;
    }
}
