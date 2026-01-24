<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\PromotionBundle\Discount\DiscountContext;

/**
 * Defines the contract for converting source entities to discount contexts.
 *
 * Implementations convert various entity types (orders, checkouts, shopping lists)
 * into a standardized DiscountContext for discount calculation and application.
 */
interface DiscountContextConverterInterface
{
    /**
     * @param object $sourceEntity
     * @return DiscountContext
     */
    public function convert($sourceEntity): DiscountContext;

    /**
     * @param object $sourceEntity
     * @return bool
     */
    public function supports($sourceEntity): bool;
}
