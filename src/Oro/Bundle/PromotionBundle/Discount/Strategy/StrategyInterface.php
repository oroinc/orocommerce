<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;

interface StrategyInterface
{
    /**
     * @param DiscountContext $discountContext
     * @param DiscountInterface[]|array $discounts
     * @return DiscountContext
     */
    public function process(DiscountContext $discountContext, array $discounts): DiscountContext;

    /**
     * @return string
     */
    public function getLabel(): string;
}
