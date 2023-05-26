<?php

namespace Oro\Bundle\PayPalBundle\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentResultMessageProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handle PayPal express checkout response.
 */
class PayflowExpressCheckoutRedirectListener
{
    public function __construct(
        private RequestStack $requestStack,
        private PaymentMethodProviderInterface $paymentMethodProvider,
        private PaymentResultMessageProviderInterface $messageProvider
    ) {
    }

    public function onReturn(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction) {
            return;
        }

        if (false === $this->paymentMethodProvider->hasPaymentMethod($paymentTransaction->getPaymentMethod())) {
            return;
        }

        if (!$paymentTransaction->isSuccessful()) {
            $this->handleFundingSourceFailError($event);
            $this->handleFailureUrl($event);
        }
    }

    private function handleFundingSourceFailError(AbstractCallbackEvent $event): void
    {
        $paymentTransaction = $event->getPaymentTransaction();
        $response = $paymentTransaction->getResponse();
        if (!empty($response['RESPMSG']) && strpos($response['RESPMSG'], '10486') !== false) {
            $flashBag = $this->requestStack->getSession()->getFlashBag();
            $flashBag->add('warning', 'oro.paypal.result.funding_decline_error');
        }
    }

    private function handleFailureUrl(AbstractCallbackEvent $event): void
    {
        $paymentTransaction = $event->getPaymentTransaction();
        $transactionOptions = $paymentTransaction->getTransactionOptions();
        if (!empty($transactionOptions['failureUrl'])) {
            $event->setResponse(new RedirectResponse($transactionOptions['failureUrl']));

            $flashBag = $this->requestStack->getSession()->getFlashBag();
            if (!$flashBag->has('error')) {
                $flashBag->add('error', $this->messageProvider->getErrorMessage($paymentTransaction));
            }
        }
    }
}
