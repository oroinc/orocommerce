<?php

namespace Oro\Bundle\PaymentBundle\Formatter;

use Oro\Bundle\PaymentBundle\Event\CollectFormattedPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides formatted payment method options
 */
class PaymentMethodOptionsFormatter
{
    /**
     * @var PaymentMethodViewProviderInterface
     */
    protected $paymentMethodViewProvider;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
        PaymentMethodViewProviderInterface $paymentMethodViewProvider,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->paymentMethodViewProvider = $paymentMethodViewProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $paymentMethod
     * @return array
     */
    public function formatPaymentMethodOptions(string $paymentMethod)
    {
        try {
            $paymentMethodView = $this->paymentMethodViewProvider->getPaymentMethodView($paymentMethod);

            $event = new CollectFormattedPaymentOptionsEvent($paymentMethodView);
            $this->eventDispatcher->dispatch($event, CollectFormattedPaymentOptionsEvent::EVENT_NAME);

            return $event->getOptions();
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }
}
