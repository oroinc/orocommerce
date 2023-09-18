<?php

namespace Oro\Bundle\CheckoutBundle\Event;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Event before checkout start to prepare checkout settings.
 */
class PrepareCheckoutSettingsEvent
{
    public const MODIFY = 'oro_checkout.workflow.prepare_checkout_settings';

    public function __construct(private Checkout $checkout, private mixed $settings)
    {
    }

    public function getCheckout(): Checkout
    {
        return $this->checkout;
    }

    public function setCheckout(Checkout $checkout): void
    {
        $this->checkout = $checkout;
    }

    public function getSettings(): mixed
    {
        return $this->settings;
    }

    public function setSettings(mixed $settings): void
    {
        $this->settings = $settings;
    }
}
