<?php

namespace Oro\Bundle\PayPalBundle\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProvidersRegistry;
use Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option as GatewayOption;
use Psr\Log\LoggerAwareTrait;

class PayflowExpressCheckoutListener
{
    use LoggerAwareTrait;

    /** @var PaymentMethodProvidersRegistry */
    protected $paymentMethodRegistry;

    /**
     * @param PaymentMethodProvidersRegistry $paymentMethodRegistry
     */
    public function __construct(PaymentMethodProvidersRegistry $paymentMethodRegistry)
    {
        $this->paymentMethodRegistry = $paymentMethodRegistry;
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onError(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction) {
            return;
        }

        $paymentTransaction
            ->setSuccessful(false)
            ->setActive(false);
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onReturn(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();
        $data = $event->getData();

        // TODO: BB-3693 Will use typed Response
        if (!$paymentTransaction || !isset($data['PayerID'], $data['token']) ||
            $data['token'] !== $paymentTransaction->getReference()
        ) {
            return;
        }

        $paymentTransaction
            ->setResponse(array_replace($paymentTransaction->getResponse(), $data));

        try {
            foreach ($this->paymentMethodRegistry->getPaymentMethodProviders() as $paymentMethodProvider) {
                if ($paymentMethodProvider->hasPaymentMethod($paymentTransaction->getPaymentMethod())) {
                    $paymentMethod = $paymentMethodProvider->getPaymentMethod($paymentTransaction->getPaymentMethod());
                    $paymentMethod->execute(PayPalExpressCheckoutPaymentMethod::COMPLETE, $paymentTransaction);
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
