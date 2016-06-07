<?php

namespace OroB2B\Bundle\PricingBundle\Event\CombinedPriceList;

use Symfony\Component\EventDispatcher\Event;

class AccountGroupCPLUpdateEvent extends Event
{
    const NAME = 'orob2b_pricing.account_group.combined_price_list.update';

    /**
     * @var array
     */
    protected $accountGroupsData;

    /**
     * @param array $accountGroupsData
     */
    public function __construct(array $accountGroupsData)
    {
        $this->accountGroupsData = $accountGroupsData;
    }

    /**
     * @return array
     */
    public function getAccountGroupsData()
    {
        return $this->accountGroupsData;
    }
}
