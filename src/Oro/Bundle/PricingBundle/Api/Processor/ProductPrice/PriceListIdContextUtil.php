<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\SharedDataAwareContextInterface;

/**
 * Provides a set of static methods to work with a price list ID stored in the context.
 */
class PriceListIdContextUtil
{
    private const PRICE_LIST_ID_ATTRIBUTE = 'price_list_id';

    /**
     * Stores the given price list ID to the context.
     */
    public static function storePriceListId(SharedDataAwareContextInterface $context, int $priceListId): void
    {
        $context->getSharedData()->set(self::PRICE_LIST_ID_ATTRIBUTE, $priceListId);
    }

    /**
     * Retrieves a price list ID from the context.
     */
    public static function getPriceListId(SharedDataAwareContextInterface $context): int
    {
        $priceListId = $context->getSharedData()->get(self::PRICE_LIST_ID_ATTRIBUTE);
        if (null === $priceListId) {
            throw new \LogicException('A price list ID is has not been set in the context.');
        }

        return $priceListId;
    }

    /**
     * Checks whether a price list ID exists in the context.
     */
    public static function hasPriceListId(SharedDataAwareContextInterface $context): bool
    {
        return $context->getSharedData()->has(self::PRICE_LIST_ID_ATTRIBUTE);
    }

    /**
     * Adds a price list ID stored in the context as a suffix to the given product price ID.
     */
    public static function normalizeProductPriceId(
        SharedDataAwareContextInterface $context,
        string $productPriceId
    ): string {
        return sprintf('%s-%d', $productPriceId, self::getPriceListId($context));
    }
}
