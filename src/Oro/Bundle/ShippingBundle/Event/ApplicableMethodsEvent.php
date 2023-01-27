<?php

namespace Oro\Bundle\ShippingBundle\Event;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired by the ShippingPriceProvider after getting all applicable shipping methods.
 * The listeners of this event may modify ShippingMethodViewCollection according to their needs.
 */
class ApplicableMethodsEvent extends Event
{
    public const NAME = 'oro_shipping.applicable_methods';

    private ShippingMethodViewCollection $methodCollection;
    private object $sourceEntity;

    public function __construct(ShippingMethodViewCollection $methodCollection, object $sourceEntity)
    {
        $this->methodCollection = $methodCollection;
        $this->sourceEntity = $sourceEntity;
    }

    public function getMethodCollection(): ShippingMethodViewCollection
    {
        return $this->methodCollection;
    }

    public function getSourceEntity(): object
    {
        return $this->sourceEntity;
    }
}
