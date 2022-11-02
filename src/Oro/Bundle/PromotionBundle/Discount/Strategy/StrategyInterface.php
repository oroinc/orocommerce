<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;

interface StrategyInterface
{
    /**
     * @param DiscountContextInterface $discountContext
     * @param DiscountInterface[]|array $discounts
     * @return DiscountContextInterface
     */
    public function process(DiscountContextInterface $discountContext, array $discounts): DiscountContextInterface;

    public function getLabel(): string;
}
