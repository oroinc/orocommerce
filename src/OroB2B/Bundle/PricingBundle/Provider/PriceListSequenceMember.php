<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class PriceListSequenceMember
{
    /** @var  PriceList */
    protected $priceList;

    /** @var  boolean */
    protected $mergeAllowed;

    /**
     * @param PriceList $priceList
     * @param boolean $mergeAllowed
     */
    public function __construct(PriceList $priceList, $mergeAllowed)
    {
        $this->priceList = $priceList;
        $this->mergeAllowed = $mergeAllowed;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @return boolean
     */
    public function isMergeAllowed()
    {
        return $this->mergeAllowed;
    }
}
