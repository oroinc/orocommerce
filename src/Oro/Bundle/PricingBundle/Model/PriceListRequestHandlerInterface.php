<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Entity\BasePriceList;

/**
 * Declares methods to obtain price list entity, selected price list currencies,
 * decide whether tier prices should be shown or not
 */
interface PriceListRequestHandlerInterface
{
    const TIER_PRICES_KEY = 'showTierPrices';
    const PRICE_LIST_CURRENCY_KEY = 'priceCurrencies';
    const PRICE_LIST_KEY = 'priceListId';

    /**
     * @return BasePriceList|null
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
