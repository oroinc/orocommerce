<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when combined price lists for customer groups are updated.
 *
 * This event carries data about which customer groups have had their combined price list
 * assignments changed, allowing listeners to react to customer group pricing updates.
 */
class CustomerGroupCPLUpdateEvent extends Event
{
    public const NAME = 'oro_pricing.customer_group.combined_price_list.update';

    /**
     * @var array
     */
    protected $customerGroupsData;

    public function __construct(array $customerGroupsData)
    {
        $this->customerGroupsData = $customerGroupsData;
    }

    /**
     * @return array
     */
    public function getCustomerGroupsData()
    {
        return $this->customerGroupsData;
    }
}
