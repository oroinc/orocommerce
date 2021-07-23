<?php

namespace Oro\Bundle\PricingBundle\Event\PricingStorage;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when Customer Group to pricing storage (Price List) relation is updated.
 */
class CustomerGroupRelationUpdateEvent extends Event
{
    public const NAME = 'oro_pricing.pricing_storage.customer_group_relation_update';

    /**
     * @var array
     */
    protected $customerGroupsData;

    public function __construct(array $customerGroupsData)
    {
        $this->customerGroupsData = $customerGroupsData;
    }

    public function getCustomerGroupsData(): array
    {
        return $this->customerGroupsData;
    }
}
