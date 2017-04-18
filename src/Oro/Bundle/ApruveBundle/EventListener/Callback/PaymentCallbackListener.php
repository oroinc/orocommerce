<?php

namespace Oro\Bundle\ApruveBundle\EventListener\Callback;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Psr\Log\LoggerAwareTrait;

class PaymentCallbackListener
{
    use LoggerAwareTrait;

    /**
     * @var PaymentMethodProviderInterface
     */
    protected $paymentMethodProvider;

    /**
     * @param PaymentMethodProviderInterface $paymentMethodProvider
     */
    public function __construct(PaymentMethodProviderInterface $paymentMethodProvider)
    {
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

        $paymentMethodId = $paymentTransaction->getPaymentMethod();

        if (false === $this->paymentMethodProvider->hasPaymentMethod($paymentMethodId)) {
            return;
        }

        $eventData = $event->getData();

        if (!array_key_exists(ApruvePaymentMethod::PARAM_ORDER_ID, $eventData)) {
            return;
        }

        $paymentTransaction->setResponse($eventData);

        try {
            $paymentMethod = $this->paymentMethodProvider->getPaymentMethod($paymentMethodId);
            $paymentMethod->execute(ApruvePaymentMethod::AUTHORIZE, $paymentTransaction);

            $event->markSuccessful();
        } catch (\InvalidArgumentException $e) {
            if ($this->logger) {
                // Do not expose sensitive data in context.
                $this->logger->error($e->getMessage(), []);
            }
        }
    }
}
