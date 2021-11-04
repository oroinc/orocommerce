<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Entity\PriceList;

/**
 * DTO contains all required information about PriceList for use in combining price list logic.
 */
class PriceListSequenceMember
{
    /** @var  PriceList */
    protected $priceList;

    /** @var bool|null */
    protected $mergeAllowed;

    /**
     * @param PriceList $priceList
     * @param bool|null $mergeAllowed
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
     * @return bool
     */
    public function isMergeAllowed()
    {
        return (bool) $this->mergeAllowed;
    }
}
