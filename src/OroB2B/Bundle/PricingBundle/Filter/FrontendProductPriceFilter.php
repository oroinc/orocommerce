<?php

namespace OroB2B\Bundle\PricingBundle\Filter;

class FrontendProductPriceFilter extends ProductPriceFilter
{
    /**
     * {@inheritDoc}
     */
    protected function getPriceList()
    {
        return $this->priceListRequestHandler->getPriceListByAccount();
    }
}
