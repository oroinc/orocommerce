<?php

namespace Oro\Bundle\PayPalBundle\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option as GatewayOption;
use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;

class PayflowExpressCheckoutRedirectListener
{
    /** @var Session */
    private $session;

    /**
     * @var PaymentMethodProviderInterface
     */
    protected $paymentMethodProvider;

    /**
     * @param Session $session
     * @param PaymentMethodProviderInterface $paymentMethodProvider
     */
    public function __construct(Session $session, PaymentMethodProviderInterface $paymentMethodProvider)
    {
        $this->session = $session;
        $this->paymentMethodProvider = $paymentMethodProvider;
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

        if (false === $this->paymentMethodProvider->hasPaymentMethod($paymentTransaction->getPaymentMethod())) {
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
