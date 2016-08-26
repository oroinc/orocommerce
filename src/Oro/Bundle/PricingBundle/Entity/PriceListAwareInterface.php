<?php

namespace Oro\Bundle\PricingBundle\Entity;

interface PriceListAwareInterface
{
    /**
     * @return PriceList
     */
    public function getPriceList();

    /**
     * @param PriceList $priceList
     * @return mixed
     */
    public function setPriceList(PriceList $priceList);

    /**
     * @return int
     */
    public function getPriority();
}
