<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedDiscountRepository;

/**
 * Provides data about AppliedDiscounts for given Orders (and OrderLineItems) (saved previously in DB)
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
     * Returns applied orders discounts by order id
     *
     * @param Order $order
     * @return AppliedDiscount[]
     */
    public function getDiscountsByOrder(Order $order): array
    {
        $orderId = $order->getId();

        $cacheKey = $this->getCacheKey($orderId);

        if ($this->cache->contains($cacheKey)) {
            return $this->cache->fetch($cacheKey);
        }

        $discounts = $this->getAppliedDiscountRepository()->findByOrder($order);

        $this->cache->save($cacheKey, $discounts);

        return $discounts;
    }

    /**
     * Returns sum of all AppliedDiscount amounts for given Order (including line item discounts, etc)
     *
     * @param Order $order
     * @return float
     */
    public function getDiscountsAmountByOrder(Order $order): float
    {
        $amount = 0.0;
        foreach ($this->getDiscountsByOrder($order) as $appliedDiscount) {
            $amount += $appliedDiscount->getAmount();
        }

        return $amount;
    }

    /**
     * Returns sum of all AppliedDiscount amounts for given OrderLineItem
     *
     * @param OrderLineItem $orderLineItem
     * @return float
     */
    public function getDiscountsAmountByLineItem(OrderLineItem $orderLineItem): float
    {
        $lineItemDiscountAmount = 0.0;

        $order = $orderLineItem->getOrder();

        foreach ($this->getDiscountsByOrder($order) as $orderAppliedDiscount) {
            $lineItem = $orderAppliedDiscount->getLineItem();

            if (!$lineItem instanceof OrderLineItem) {
                continue;
            }

            if ($lineItem->getId() === $orderLineItem->getId()) {
                $lineItemDiscountAmount += $orderAppliedDiscount->getAmount();
            }
        }

        return $lineItemDiscountAmount;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository|AppliedDiscountRepository
     */
    protected function getAppliedDiscountRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(AppliedDiscount::class);
    }

    /**
     * @param int $orderId
     * @return string
     */
    protected function getCacheKey(int $orderId): string
    {
        return self::CACHE_PREFIX . $orderId;
    }
}
