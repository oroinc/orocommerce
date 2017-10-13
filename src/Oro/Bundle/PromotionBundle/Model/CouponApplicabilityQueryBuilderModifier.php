<?php

namespace Oro\Bundle\PromotionBundle\Model;

use Doctrine\ORM\QueryBuilder;

/**
 * Modify coupons query builder according applicability rules.
 * Only coupons with relevant "Valid Until" date should be applicable.
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
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->gte($alias . '.validUntil', ':now'),
                $queryBuilder->expr()->isNull($alias . '.validUntil')
            ))
            ->andWhere($queryBuilder->expr()->eq($alias . '.enabled', ':enabled'))
            ->setParameter('now', new \DateTime('now', new \DateTimeZone('UTC')))
            ->setParameter('enabled', true);
    }
}
