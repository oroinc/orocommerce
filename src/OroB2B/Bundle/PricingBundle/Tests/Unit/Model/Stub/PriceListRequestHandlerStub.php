<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\Stub;

use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandlerInterface;

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
