<?php

namespace Oro\Bundle\PricingBundle\Event;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\EventDispatcher\Event;

class ProductPriceSaveAfterEvent extends Event
{
    const NAME = 'oro_pricing.product_price.save_after';

    /**
     * @var PreUpdateEventArgs
     */
    protected $eventArgs;

    /**
     * @param PreUpdateEventArgs $eventArgs
     */
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
