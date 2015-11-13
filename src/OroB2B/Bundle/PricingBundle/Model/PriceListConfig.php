<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class PriceListConfig
{
    const CLASS_NAME = 'OroB2B\Bundle\PricingBundle\Model\PriceListConfig';

    /**
     * @var  PriceList
     */
    private $priceList;

    /**
     * @var $integer
     */
    private $priority;

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @param PriceList $priceList
     */
    public function setPriceList($priceList)
    {
        $this->priceList = $priceList;
    }

    /**
     * @return mixed
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param mixed $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }
}
