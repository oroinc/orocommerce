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
    protected $sortOrder;

    /**
     * @var boolean
     */
    protected $mergeAllowed;

    /**
     * @param PriceList|null $priceList
     * @param int|string|null $sortOrder
     * @param null|boolean $mergeAllowed
     */
    public function __construct(PriceList $priceList = null, $sortOrder = null, $mergeAllowed = null)
    {
        $this->priceList = $priceList;
        $this->sortOrder = $sortOrder;
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
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int|string $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = (int)$sortOrder;

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
