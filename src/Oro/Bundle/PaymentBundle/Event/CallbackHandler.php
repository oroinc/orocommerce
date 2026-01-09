<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles payment callback events from external payment gateways.
 *
 * This handler processes callback events by dispatching them to registered listeners,
 * persisting any changes to the associated payment transaction, and returning an appropriate
 * HTTP response to the payment gateway.
 */
class CallbackHandler
{
    use LoggerAwareTrait;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        PaymentTransactionProvider $paymentTransactionProvider
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * @param AbstractCallbackEvent $event
     * @return Response
     */
    public function handle(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();
        if (!$paymentTransaction) {
            return $event->getResponse();
        }

        $this->eventDispatcher->dispatch($event, $event->getEventName());
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        return $event->getResponse();
    }
}
