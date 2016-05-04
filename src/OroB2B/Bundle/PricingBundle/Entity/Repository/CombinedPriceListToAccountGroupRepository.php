<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;

class CombinedPriceListToAccountGroupRepository extends PriceListToAccountGroupRepository
{
    use BasicCombinedRelationRepositoryTrait;

    public function deleteInvalidRelations()
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->select('relation.id')
            ->leftJoin(
                'OroB2BPricingBundle:PriceListToAccountGroup',
                'baseRelation',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('relation.accountGroup', 'baseRelation.accountGroup'),
                    $qb->expr()->eq('relation.website', 'baseRelation.website')
                )
            )
            ->where($qb->expr()->isNull('baseRelation.accountGroup'));
        $result = $qb->getQuery()->getScalarResult();
        $invalidRelationIds = array_map('current', $result);
        if ($invalidRelationIds) {
            $qb = $this->createQueryBuilder('relation');
            $qb->delete()->where($qb->expr()->in('relation.id', $invalidRelationIds));
            $qb->getQuery()->execute();
        }
    }
}
