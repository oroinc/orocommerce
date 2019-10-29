<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Provides a set of static methods to work with a price list ID stored in the context.
 */
class PriceListIdContextUtil
{
    private const PRICE_LIST_ID_ATTRIBUTE = 'price_list_id';

    /**
     * Stores the given price list ID to the context.
     *
     * @param ContextInterface $context
     * @param int              $priceListId
     */
    public static function storePriceListId(ContextInterface $context, int $priceListId): void
    {
        $context->set(self::PRICE_LIST_ID_ATTRIBUTE, $priceListId);
    }

    /**
     * Retrieves a price list ID from the context.
     *
     * @param ContextInterface $context
     *
     * @return int
     */
    public static function getPriceListId(ContextInterface $context): int
    {
        $priceListId = $context->get(self::PRICE_LIST_ID_ATTRIBUTE);
        if (null === $priceListId) {
            throw new \LogicException('A price list ID is has not been set in the context.');
        }

        return $priceListId;
    }

    /**
     * Adds a price list ID stored in the context as a suffix to the given product price ID.
     *
     * @param ContextInterface $context
     * @param string           $productPriceId
     *
     * @return string
     */
    public static function normalizeProductPriceId(ContextInterface $context, string $productPriceId): string
    {
        return sprintf('%s-%d', $productPriceId, self::getPriceListId($context));
    }
}
