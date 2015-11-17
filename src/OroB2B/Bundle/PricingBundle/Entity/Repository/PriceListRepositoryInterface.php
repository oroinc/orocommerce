<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

interface PriceListRepositoryInterface
{
    /**
     * @param object $account
     * @param Website $website
     * @return PriceList[]
     */
    public function getPriceLists($account, Website $website);
}
