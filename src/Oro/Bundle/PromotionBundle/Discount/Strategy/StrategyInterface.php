<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;

/**
 * Defines the contract for discount application strategies.
 *
 * Implementations determine how multiple discounts are processed and applied to a discount context,
 * supporting different strategies like applying all discounts or selecting the most profitable combination.
 */
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
