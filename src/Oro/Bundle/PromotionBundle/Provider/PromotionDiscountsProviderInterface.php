<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;

/**
 * Describers interface which gets applicable discounts for a given source entity and properly configures them.
 */
interface PromotionDiscountsProviderInterface
{
    /**
     * @param object $sourceEntity
     * @param DiscountContext $context
     * @return DiscountInterface[]
     */
    public function getDiscounts($sourceEntity, DiscountContext $context): array;
}
