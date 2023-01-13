<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\Promotion;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProviderInterface;

/**
 * Provides empty discounts for the order which has subOrders.
 */
class MainOrderPromotionDiscountsProviderDecorator implements PromotionDiscountsProviderInterface
{
    private PromotionDiscountsProviderInterface $baseDiscountsProvider;

    public function __construct(PromotionDiscountsProviderInterface $baseDiscountsProvider)
    {
        $this->baseDiscountsProvider = $baseDiscountsProvider;
    }

    /**
     * Promotions should not be applied to orders with subOrders. For this cases return empty promotions set for such
     * orders.
     *
     * @param object $sourceEntity
     * @param DiscountContextInterface $context
     * @return array
     */
    public function getDiscounts($sourceEntity, DiscountContextInterface $context): array
    {
        if ($sourceEntity instanceof Order && !$sourceEntity->getSubOrders()->isEmpty()) {
            return [];
        }

        return $this->baseDiscountsProvider->getDiscounts($sourceEntity, $context);
    }
}
