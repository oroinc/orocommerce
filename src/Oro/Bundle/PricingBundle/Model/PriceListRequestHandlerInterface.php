<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;

interface PriceListRequestHandlerInterface
{
    const TIER_PRICES_KEY = 'showTierPrices';
    const WEBSITE_KEY = 'websiteId';
    const PRICE_LIST_CURRENCY_KEY = 'priceCurrencies';
    const PRICE_LIST_KEY = 'priceListId';
    const ACCOUNT_ID_KEY = 'account_id';

    /**
     * On frontend returns PriceList for logged in user
     * On backend returns PriceList for account specified in request
     *
     * @return BasePriceList
     */
    public function getPriceListByAccount();

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
