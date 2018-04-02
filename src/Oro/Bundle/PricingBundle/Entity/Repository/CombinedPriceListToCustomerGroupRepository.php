<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;

class CombinedPriceListToCustomerGroupRepository extends PriceListToCustomerGroupRepository
{
    use BasicCombinedRelationRepositoryTrait;

    public function deleteInvalidRelations()
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->select('relation.id')
            ->leftJoin(
                'OroPricingBundle:PriceListCustomerGroupFallback',
                'fallback',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('fallback.customerGroup', 'relation.customerGroup'),
                    $qb->expr()->eq('fallback.website', 'relation.website')
                )
            )
            ->leftJoin(
                'OroPricingBundle:PriceListToCustomerGroup',
                'baseRelation',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('relation.customerGroup', 'baseRelation.customerGroup'),
                    $qb->expr()->eq('relation.website', 'baseRelation.website')
                )
            )
            ->where($qb->expr()->isNull('baseRelation.customerGroup'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('fallback.fallback', PriceListCustomerGroupFallback::WEBSITE),
                    $qb->expr()->isNull('fallback.fallback')
                )
            );
        $result = $qb->getQuery()->getScalarResult();
        $invalidRelationIds = array_map('current', $result);
        if ($invalidRelationIds) {
            $qb = $this->createQueryBuilder('relation');
            $qb->delete()->where($qb->expr()->in('relation.id', ':invalidRelationIds'))
                ->setParameter(':invalidRelationIds', $invalidRelationIds);
            $qb->getQuery()->execute();
        }
    }
}
