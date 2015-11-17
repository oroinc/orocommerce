<?php

namespace OroB2B\Bundle\PricingBundle\SystemConfig;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class PriceListConfig
{
    const CLASS_NAME = 'OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfig';

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
     * @return $this
     */
    public function setPriceList($priceList)
    {
        $this->priceList = $priceList;

        return $this;
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
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }
}
