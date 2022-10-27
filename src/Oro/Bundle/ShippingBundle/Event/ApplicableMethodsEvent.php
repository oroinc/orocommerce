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
    const NAME = 'oro_shipping.applicable_methods';

    /**
     * @var ShippingMethodViewCollection
     */
    private $methodCollection;

    /**
     * @var object
     */
    private $sourceEntity;

    /**
     * @param ShippingMethodViewCollection $methodCollection
     * @param object $sourceEntity
     */
    public function __construct(ShippingMethodViewCollection $methodCollection, $sourceEntity)
    {
        $this->methodCollection = $methodCollection;
        $this->sourceEntity = $sourceEntity;
    }

    public function getMethodCollection(): ShippingMethodViewCollection
    {
        return $this->methodCollection;
    }

    /**
     * @return object
     */
    public function getSourceEntity()
    {
        return $this->sourceEntity;
    }
}
