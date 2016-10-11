<?php

namespace Oro\Bundle\PayPalBundle\EventListener\Callback;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option as GatewayOption;
use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;

class PayflowExpressCheckoutRedirectListener
{
    /** @var Session */
    private $session;

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
    public function onReturn(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction) {
            return;
        }

        if (!$paymentTransaction->isSuccessful()) {
            $transactionOptions = $paymentTransaction->getTransactionOptions();

            if (!empty($transactionOptions['failureUrl'])) {
                $event->setResponse(new RedirectResponse($transactionOptions['failureUrl']));
                $this->setErrorMessage();
            }
        }
    }

    private function setErrorMessage()
    {
        $flashBag = $this->session->getFlashBag();

        if (!$flashBag->has('error')) {
            $flashBag->add('error', 'oro.payment.result.error');
        }
    }
}
