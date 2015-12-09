<?php

namespace OroB2B\Bundle\PricingBundle\SystemConfig;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAwareInterface;

class PriceListConfig implements PriceListAwareInterface
{
    /**
     * @var PriceList
     */
    protected $priceList;

    /**
     * @var $integer
     */
    protected $priority;

    /**
     * @param PriceList|null $priceList
     * @param int|string|null $priority
     */
    public function __construct(PriceList $priceList = null, $priority = null)
    {
        $this->priceList = $priceList;
        $this->priority = $priority;
    }

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
    public function setPriceList(PriceList $priceList)
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
     * @param int|string $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = (int) $priority;

        return $this;
    }
}
