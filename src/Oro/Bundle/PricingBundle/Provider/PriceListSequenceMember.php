<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Entity\PriceList;

/**
 * DTO contains all required information about PriceList for use in combining price list logic.
 */
class PriceListSequenceMember
{
    protected PriceList $priceList;

    protected ?bool $mergeAllowed;

    public function __construct(PriceList $priceList, ?bool $mergeAllowed)
    {
        $this->priceList = $priceList;
        $this->mergeAllowed = $mergeAllowed;
    }

    public function getPriceList(): PriceList
    {
        return $this->priceList;
    }

    public function isMergeAllowed(): bool
    {
        return (bool) $this->mergeAllowed;
    }
}
