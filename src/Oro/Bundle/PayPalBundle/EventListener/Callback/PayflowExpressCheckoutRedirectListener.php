<?php

namespace Oro\Bundle\PayPalBundle\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentResultMessageProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Handle PayPal express checkout response.
 */
class PayflowExpressCheckoutRedirectListener
{
    /** @var Session */
    private $session;

    /**
     * @var PaymentMethodProviderInterface
     */
    protected $paymentMethodProvider;

    /**
     * @var PaymentResultMessageProviderInterface
     */
    protected $messageProvider;

    public function __construct(
        Session $session,
        PaymentMethodProviderInterface $paymentMethodProvider,
        PaymentResultMessageProviderInterface $messageProvider
    ) {
        $this->session = $session;
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->messageProvider = $messageProvider;
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
            $flashBag = $this->session->getFlashBag();
            $flashBag->add('warning', 'oro.paypal.result.funding_decline_error');
        }
    }

    private function handleFailureUrl(AbstractCallbackEvent $event): void
    {
        $paymentTransaction = $event->getPaymentTransaction();
        $transactionOptions = $paymentTransaction->getTransactionOptions();
        if (!empty($transactionOptions['failureUrl'])) {
            $event->setResponse(new RedirectResponse($transactionOptions['failureUrl']));

            $flashBag = $this->session->getFlashBag();
            if (!$flashBag->has('error')) {
                $flashBag->add('error', $this->messageProvider->getErrorMessage($paymentTransaction));
            }
        }
    }
}
