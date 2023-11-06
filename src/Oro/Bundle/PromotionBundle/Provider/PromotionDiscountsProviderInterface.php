<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;

/**
 * Represents a service to get applicable discounts for a given source entity and properly configures them.
 */
interface PromotionDiscountsProviderInterface
{
    /**
     * @param object                   $sourceEntity
     * @param DiscountContextInterface $context
     *
     * @return DiscountInterface[]
     */
    public function getDiscounts(object $sourceEntity, DiscountContextInterface $context): array;
}
