<?php

namespace Oro\Bundle\PricingBundle\Event\PricingStorage;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when Customer to pricing storage (Price List) relation is updated.
 */
class CustomerRelationUpdateEvent extends Event
{
    public const NAME = 'oro_pricing.pricing_storage.customer_relation_update';

    /**
     * @var array
     */
    protected $customersData;

    public function __construct(array $customersData)
    {
        $this->customersData = $customersData;
    }

    public function getCustomersData(): array
    {
        return $this->customersData;
    }
}
