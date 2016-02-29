<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model\Stub;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandlerInterface;

class PriceListRequestHandlerStub implements PriceListRequestHandlerInterface
{
    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return new PriceList();
    }

    /**
     * @param BasePriceList $priceList
     * @return array
     */
    public function getPriceListSelectedCurrencies(BasePriceList $priceList)
    {
        return [];
    }

    /**
     * @return CombinedPriceList
     */
    public function getPriceListByAccount()
    {
        return new CombinedPriceList();
    }

    /**
     * @return bool
     */
    public function getShowTierPrices()
    {
        return true;
    }
}
