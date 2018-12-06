<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\Stub;

use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandlerInterface;

class PriceListRequestHandlerStub implements PriceListRequestHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPriceListSelectedCurrencies(BasePriceList $priceList)
    {
        return [];
    }

    /**
     * @return CombinedPriceList
     */
    public function getPriceListByCustomer()
    {
        return new CombinedPriceList();
    }

    /**
     * {@inheritdoc}
     */
    public function getShowTierPrices()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceList()
    {
        return null;
    }
}
