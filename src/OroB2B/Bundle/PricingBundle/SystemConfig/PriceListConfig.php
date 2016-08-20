<?php

namespace Oro\Bundle\PricingBundle\SystemConfig;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListAwareInterface;

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
     * @var boolean
     */
    protected $mergeAllowed;

    /**
     * @param PriceList|null $priceList
     * @param int|string|null $priority
     * @param null|boolean $mergeAllowed
     */
    public function __construct(PriceList $priceList = null, $priority = null, $mergeAllowed = null)
    {
        $this->priceList = $priceList;
        $this->priority = $priority;
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
        $this->priority = (int)$priority;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMergeAllowed()
    {
        return $this->mergeAllowed;
    }

    /**
     * @param boolean $mergeAllowed
     */
    public function setMergeAllowed($mergeAllowed)
    {
        $this->mergeAllowed = $mergeAllowed;
    }
}
