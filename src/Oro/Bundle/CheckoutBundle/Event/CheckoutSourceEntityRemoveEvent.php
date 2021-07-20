<?php

namespace Oro\Bundle\CheckoutBundle\Event;

use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Fires within checkout workflow and notify about automatic removing checkout source entity
 * that have place because customer selected this action within checkout process.
 */
class CheckoutSourceEntityRemoveEvent extends Event
{
    public const BEFORE_REMOVE = 'oro_checkout.checkout_source_entity_remove.before';
    public const AFTER_REMOVE = 'oro_checkout.checkout_source_entity_remove.after';

    /** @var CheckoutSourceEntityInterface */
    private $checkoutSourceEntity;

    public function __construct(CheckoutSourceEntityInterface $checkoutSourceEntity)
    {
        $this->checkoutSourceEntity = $checkoutSourceEntity;
    }

    public function getCheckoutSourceEntity(): CheckoutSourceEntityInterface
    {
        return $this->checkoutSourceEntity;
    }
}
