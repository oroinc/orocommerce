<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event to collect payment method options based on payment method view
 */
class CollectFormattedPaymentOptionsEvent extends Event
{
    const EVENT_NAME = 'oro_payment.collect_formatted_payment_options';

    /**
     * @var PaymentMethodViewInterface
     */
    private $paymentMethodView;

    /**
     * @var array
     */
    private $options;

    public function __construct(PaymentMethodViewInterface $paymentMethodView, array $options = [])
    {
        $this->paymentMethodView = $paymentMethodView;
        $this->options = $options;
    }

    public function getPaymentMethodView(): PaymentMethodViewInterface
    {
        return $this->paymentMethodView;
    }

    /**
     * @return array|string[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array|string[] $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param string $option
     * @return $this
     */
    public function addOption(string $option)
    {
        $this->options[] = $option;

        return $this;
    }
}
