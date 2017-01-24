<?php

namespace Oro\Bundle\PayPalBundle\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface;
use Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseStatusMap;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Session\Session;

class PayflowListener
{
    use LoggerAwareTrait;
    
    /** @var Session */
    protected $session;

    /** @var PaymentMethodProvidersRegistryInterface */
    protected $paymentMethodRegistry;

    /**
     * @param Session $session
     * @param PaymentMethodProvidersRegistryInterface $paymentMethodRegistry
     */
    public function __construct(Session $session, PaymentMethodProvidersRegistryInterface $paymentMethodRegistry)
    {
        $this->session = $session;
        $this->paymentMethodRegistry = $paymentMethodRegistry;
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onError(AbstractCallbackEvent $event)
    {
        $eventData = $event->getData();
        $response = new Response($eventData);

        if (in_array($response->getResult(), [ResponseStatusMap::SECURE_TOKEN_EXPIRED], true)) {
            $this->session->getFlashBag()->set('warning', 'oro.paypal.result.token_expired');
        }
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onNotify(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction || $paymentTransaction->getReference()) {
            return;
        }

        $data = $event->getData();

        $paymentTransaction
            ->setResponse(array_replace($paymentTransaction->getResponse(), $data));

        try {
            foreach ($this->paymentMethodRegistry->getPaymentMethodProviders() as $paymentMethodProvider) {
                if ($paymentMethodProvider->hasPaymentMethod($paymentTransaction->getPaymentMethod())) {
                    $paymentMethod = $paymentMethodProvider->getPaymentMethod($paymentTransaction->getPaymentMethod());
                    $paymentMethod->execute(PayPalCreditCardPaymentMethod::COMPLETE, $paymentTransaction);
                    $event->markSuccessful();
                }
            }
        } catch (\InvalidArgumentException $e) {
            if ($this->logger) {
                // do not expose sensitive data in context
                $this->logger->error($e->getMessage(), []);
            }
        }
    }
}
