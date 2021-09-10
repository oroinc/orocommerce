<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Entity\PriceList;

/**
 * DTO contains all required information about PriceList for use in combining price list logic.
 */
class PriceListSequenceMember
{
    /** @var PriceList */
    protected PriceList $priceList;

    /** @var bool|null */
    protected ?bool $mergeAllowed;

    /**
     * @param PriceList $priceList
     * @param bool|null $mergeAllowed
     */
    public function __construct(PriceList $priceList, ?bool $mergeAllowed)
    {
        $this->priceList = $priceList;
        $this->mergeAllowed = $mergeAllowed;
    }

    /**
     * @return PriceList
     */
    public function getPriceList(): PriceList
    {
        return $this->priceList;
    }

    /**
     * @return bool
     */
    public function isMergeAllowed(): bool
    {
        return (bool) $this->mergeAllowed;
    }
}
