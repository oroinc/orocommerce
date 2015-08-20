<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model\Stub;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\AbstractPriceListRequestHandler;

class PriceListRequestHandlerStub extends AbstractPriceListRequestHandler
{
    /**
     * {@inheritDoc}
     */
    public function getPriceList()
    {
        return new PriceList();
    }

    /**
     * {@inheritDoc}
     */
    public function getPriceListSelectedCurrencies()
    {
        return [];
    }
}
