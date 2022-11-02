<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Symfony\Contracts\EventDispatcher\Event;

class CustomerGroupCPLUpdateEvent extends Event
{
    const NAME = 'oro_pricing.customer_group.combined_price_list.update';

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
