<?php

namespace Oro\Bundle\PromotionBundle\Model;

use Doctrine\ORM\QueryBuilder;

/**
 * Modify coupons query builder according applicability rules.
 * Only coupons with promotions and relevant "Valid Until" date should be applicable.
 */
class CouponApplicabilityQueryBuilderModifier
{
    /**
     * @param QueryBuilder $queryBuilder
     */
    public function modify(QueryBuilder $queryBuilder)
    {
        $aliases = $queryBuilder->getRootAliases();
        $alias = reset($aliases);

        $queryBuilder
            ->andWhere($queryBuilder->expr()->isNotNull($alias . '.promotion'))
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->gt($alias . '.validUntil', ':now'),
                $queryBuilder->expr()->isNull($alias . '.validUntil')
            ))
            ->setParameter('now', new \DateTime('now', new \DateTimeZone('UTC')));
    }
}
