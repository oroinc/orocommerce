<?php

namespace Oro\Bundle\PayPalBundle\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentResultMessageProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

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

    /**
     * @param Session $session
     * @param PaymentMethodProviderInterface $paymentMethodProvider
     * @param PaymentResultMessageProviderInterface $messageProvider
     */
    public function __construct(
        Session $session,
        PaymentMethodProviderInterface $paymentMethodProvider,
        PaymentResultMessageProviderInterface $messageProvider
    ) {
        $this->session = $session;
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->messageProvider = $messageProvider;
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

                $flashBag = $this->session->getFlashBag();
                if (!$flashBag->has('error')) {
                    $flashBag->add('error', $this->messageProvider->getErrorMessage($paymentTransaction));
                }
            }
        }
    }
}
