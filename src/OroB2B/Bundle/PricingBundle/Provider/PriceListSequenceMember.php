<?php


namespace OroB2B\Bundle\PricingBundle\Provider;


use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class PriceListSequenceMember
{
    /** @var  PriceList */
    protected $priceList;

    /** @var  boolean */
    protected $allowMerge;

    /**
     * @param PriceList $priceList
     * @param boolean $allowMerge
     */
    public function __construct(PriceList $priceList, $allowMerge)
    {
        $this->priceList = $priceList;
        $this->allowMerge = $allowMerge;
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
    public function isAllowMerge()
    {
        return $this->allowMerge;
    }
}
