<?php

namespace Oro\Bundle\PricingBundle\Event;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a product price is saved.
 *
 * This event carries the ORM event arguments containing information about the saved product price,
 * allowing listeners to react to product price changes.
 */
class ProductPriceSaveAfterEvent extends Event
{
    public const NAME = 'oro_pricing.product_price.save_after';

    /**
     * @var PreUpdateEventArgs
     */
    protected $eventArgs;

    public function __construct(PreUpdateEventArgs $eventArgs)
    {
        $this->eventArgs = $eventArgs;
    }

    /**
     * @return PreUpdateEventArgs
     */
    public function getEventArgs()
    {
        return $this->eventArgs;
    }
}
