<?php

namespace Oro\Bundle\PricingBundle\SystemConfig;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListAwareInterface;

/**
 * DTO that contains the configuration of a single default price list
 */
class PriceListConfig implements PriceListAwareInterface
{
    /**
     * @var PriceList|null
     */
    protected $priceList;

    /**
     * @var int|null
     */
    protected $sortOrder;

    /**
     * @var bool|null
     */
    protected $mergeAllowed;

    /**
     * @param PriceList|null $priceList
     * @param int|null $sortOrder
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
     * @return int|null
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int|null $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = (int)$sortOrder;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isMergeAllowed(): ?bool
    {
        return $this->mergeAllowed;
    }

    /**
     * @param bool|null $mergeAllowed
     */
    public function setMergeAllowed(?bool $mergeAllowed)
    {
        $this->mergeAllowed = $mergeAllowed;
    }
}
