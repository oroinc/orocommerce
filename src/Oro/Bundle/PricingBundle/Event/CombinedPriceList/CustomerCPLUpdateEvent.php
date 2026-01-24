<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when combined price lists for customers are updated.
 *
 * This event carries data about which customers have had their combined price list
 * assignments changed, allowing listeners to react to customer-specific pricing updates.
 */
class CustomerCPLUpdateEvent extends Event
{
    const NAME = 'oro_pricing.customer.combined_price_list.update';

    /**
     * @var array
     */
    protected $customersData;

    public function __construct(array $customersData)
    {
        $this->customersData = $customersData;
    }

    /**
     * @return array
     */
    public function getCustomersData()
    {
        return $this->customersData;
    }
}
