<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice;

use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Stores PriceList id by context
 */
interface PriceListIDContextStorageInterface
{
    /**
     * @param int              $priceListID
     * @param ContextInterface $context
     *
     * @return self
     */
    public function store(int $priceListID, ContextInterface $context) : self;

    /**
     * @param ContextInterface $context
     *
     * @return int
     */
    public function get(ContextInterface $context) : int;
}
