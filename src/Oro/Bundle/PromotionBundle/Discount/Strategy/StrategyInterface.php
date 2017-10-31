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

    /**
     * @return string
     */
    public function getLabel(): string;
}
