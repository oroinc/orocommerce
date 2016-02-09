<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

interface PriceListRequestHandlerInterface
{
    /**
     * On frontend returns PriceList for logged in user
     * On backend returns PriceList for account specified in request
     *
     * @param Account $account
     * @param Website $website
     * @return BasePriceList
     */
    public function getPriceListByAccount(Account $account = null, Website $website = null);

    /**
     * Return PriceLists by data from request or default
     *
     * @return null|PriceList
     */
    public function getPriceList();

    /**
     * @return bool
     */
    public function getShowTierPrices();

    /**
     * @param BasePriceList $priceList
     * @return string[]
     */
    public function getPriceListSelectedCurrencies(BasePriceList $priceList);
}
