<?php

namespace Oro\Bundle\CheckoutBundle\Event;

use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Fires within checkout workflow and notifies about automatic clearing of the checkout source entity.
 */
class CheckoutSourceEntityClearEvent extends Event
{
    public const NAME = 'oro_checkout.checkout_source_entity_clear';

    private CheckoutSourceEntityInterface $checkoutSourceEntity;

    public function __construct(CheckoutSourceEntityInterface $checkoutSourceEntity)
    {
        $this->checkoutSourceEntity = $checkoutSourceEntity;
    }

    public function getCheckoutSourceEntity(): CheckoutSourceEntityInterface
    {
        return $this->checkoutSourceEntity;
    }
}
