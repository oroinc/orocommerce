<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;

/**
 * Describers interface which gets applicable discounts for a given source entity and properly configures them.
 */
interface PromotionDiscountsProviderInterface
{
    /**
     * @param object $sourceEntity
     * @param DiscountContextInterface $context
     * @return DiscountInterface[]
     */
    public function getDiscounts($sourceEntity, DiscountContextInterface $context): array;
}
