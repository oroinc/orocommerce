<?php

namespace Oro\Bundle\PromotionBundle\Api;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;

/**
 * Provides discounts for order line items and cache it in API context.
 */
class OrderLineItemDiscountProvider
{
    private const DISCOUNTS_CONTEXT_KEY = '_discounts';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param CustomizeLoadedDataContext $context
     * @param int[]                      $lineItemIds
     *
     * @return array [line item id => discount amount, ...]
     */
    public function getDiscounts(CustomizeLoadedDataContext $context, array $lineItemIds): array
    {
        if (!$context->has(self::DISCOUNTS_CONTEXT_KEY)) {
            $context->set(self::DISCOUNTS_CONTEXT_KEY, $this->loadDiscounts($lineItemIds));
        }

        return $context->get(self::DISCOUNTS_CONTEXT_KEY);
    }

    /**
     * @param array $lineItemIds
     *
     * @return array [lineItemId => discount amount, ...]
     */
    private function loadDiscounts(array $lineItemIds): array
    {
        if (empty($lineItemIds)) {
            return [];
        }

        $qb = $this->doctrineHelper->getEntityManagerForClass(AppliedDiscount::class)
            ->createQueryBuilder()
            ->from(AppliedDiscount::class, 'discount')
            ->innerJoin('discount.lineItem', 'lineItem')
            ->select('lineItem.id AS lineItemId, SUM(discount.amount) AS amount')
            ->groupBy('lineItem.id')
            ->where('lineItem.id IN (:lineItemIds)')
            ->setParameter('lineItemIds', $lineItemIds);

        $result = [];
        $rows = $qb->getQuery()->getArrayResult();
        foreach ($rows as $row) {
            $result[$row['lineItemId']] = $row['amount'];
        }

        return $result;
    }
}
