<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\Promotion;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProviderInterface;

/**
 * Provides empty discounts for an order that has sub orders.
 * For such orders promotions should not be applied.
 */
class MainOrderPromotionDiscountsProviderDecorator implements PromotionDiscountsProviderInterface
{
    public function __construct(
        private PromotionDiscountsProviderInterface $baseDiscountsProvider
    ) {
    }

    #[\Override]
    public function getDiscounts(object $sourceEntity, DiscountContextInterface $context): array
    {
        if ($sourceEntity instanceof Order && !$sourceEntity->getSubOrders()->isEmpty()) {
            return [];
        }

        return $this->baseDiscountsProvider->getDiscounts($sourceEntity, $context);
    }
}
