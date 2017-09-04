<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice;

use Oro\Component\ChainProcessor\ContextInterface;

class PriceListIDInContextStorage implements PriceListIDContextStorageInterface
{
    /**
     * @internal
     */
    const CONTEXT_ATTRIBUTE_PRICE_LIST_ID = 'price_list_id';

    /**
     * @inheritDoc
     */
    public function store(int $priceListID, ContextInterface $context): PriceListIDContextStorageInterface
    {
        $context->set(self::CONTEXT_ATTRIBUTE_PRICE_LIST_ID, $priceListID);

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @throws \Exception
     */
    public function get(ContextInterface $context): int
    {
        if (false === $context->has(self::CONTEXT_ATTRIBUTE_PRICE_LIST_ID)) {
            throw new \Exception('Price list has not been set in context');
        }

        return $context->get(self::CONTEXT_ATTRIBUTE_PRICE_LIST_ID);
    }
}
