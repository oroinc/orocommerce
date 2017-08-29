<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;

/**
 * Provides data about discounts for given Orders (and OrderLineItems) (saved previously in DB)
 * Used on Order view pages
 */
class AppliedDiscountsProvider
{
    const CACHE_PREFIX = 'oro_promotion.provider.applied_discounts_provider:';

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param Cache $cache
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(Cache $cache, DoctrineHelper $doctrineHelper)
    {
        $this->cache = $cache;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Returns sum of all discounts amounts for given Order (including line item discounts, etc) without shipping
     *
     * @param Order $order
     * @return float
     */
    public function getDiscountsAmountByOrder(Order $order): float
    {
        $amount = 0.0;
        foreach ($this->getPromotionsByOrder($order) as $appliedPromotion) {
            if ($appliedPromotion->getType() === ShippingDiscount::NAME) {
                continue;
            }

            $amount += $this->getDiscountsSum($appliedPromotion);
        }

        return $amount;
    }

    /**
     * Returns sum of all shipping discounts amounts for given Order
     *
     * @param Order $order
     * @return float
     */
    public function getShippingDiscountsAmountByOrder(Order $order): float
    {
        $amount = 0.0;
        foreach ($this->getPromotionsByOrder($order) as $appliedPromotion) {
            if ($appliedPromotion->getType() !== ShippingDiscount::NAME) {
                continue;
            }

            $amount += $this->getDiscountsSum($appliedPromotion);
        }

        return $amount;
    }

    /**
     * Returns sum of all discounts amounts for given OrderLineItem
     *
     * @param OrderLineItem $orderLineItem
     * @return float
     */
    public function getDiscountsAmountByLineItem(OrderLineItem $orderLineItem): float
    {
        if (!$orderLineItem->getId()) {
            throw new \LogicException('Cant determine discount for non-saved line item');
        }
        $lineItemDiscountAmount = 0.0;

        $order = $orderLineItem->getOrder();

        foreach ($this->getPromotionsByOrder($order) as $appliedPromotion) {
            foreach ($appliedPromotion->getAppliedDiscounts() as $appliedDiscount) {
                $lineItem = $appliedDiscount->getLineItem();

                if (!$lineItem instanceof OrderLineItem) {
                    continue;
                }

                if ($lineItem->getId() === $orderLineItem->getId()) {
                    $lineItemDiscountAmount += $appliedDiscount->getAmount();
                }
            }
        }

        return $lineItemDiscountAmount;
    }

    /**
     * Returns applied promotions by order id
     *
     * @param Order $order
     * @return AppliedPromotion[]
     */
    protected function getPromotionsByOrder(Order $order): array
    {
        $orderId = $order->getId();
        if (!$orderId) {
            throw new \LogicException('Cant determine discount for non-saved order');
        }

        $cacheKey = $this->getCacheKey($orderId);

        if ($this->cache->contains($cacheKey)) {
            return $this->cache->fetch($cacheKey);
        }

        $appliedPromotions = $this->getAppliedPromotionRepository()->findByOrder($order);

        $this->cache->save($cacheKey, $appliedPromotions);

        return $appliedPromotions;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository|AppliedPromotionRepository
     */
    protected function getAppliedPromotionRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(AppliedPromotion::class);
    }

    /**
     * @param int $orderId
     * @return string
     */
    protected function getCacheKey(int $orderId): string
    {
        return self::CACHE_PREFIX . $orderId;
    }

    /**
     * @param AppliedPromotion $appliedPromotion
     * @return float float
     */
    protected function getDiscountsSum(AppliedPromotion $appliedPromotion)
    {
        $amount = 0.0;
        foreach ($appliedPromotion->getAppliedDiscounts() as $appliedDiscount) {
            $amount += $appliedDiscount->getAmount();
        }

        return $amount;
    }
}
