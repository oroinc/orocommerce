<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;

class CombinedPriceListToAccountRepository extends PriceListToAccountRepository
{
    use BasicCombinedRelationRepositoryTrait;

    public function deleteInvalidRelations()
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->select('relation')
            ->leftJoin(
                'OroB2BPricingBundle:PriceListToAccount',
                'baseRelation',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('relation.account', 'baseRelation.account'),
                    $qb->expr()->eq('relation.website', 'baseRelation.website')
                )
            )
        ->where($qb->expr()->isNull('baseRelation.account'));
        $result = $qb->getQuery()->getScalarResult();
        $invalidRelationIds = array_map('current', $result);
        if ($invalidRelationIds) {
            $qb = $this->createQueryBuilder('relation');
            $qb->delete()->where($qb->expr()->in('relation.id', $invalidRelationIds));
            $qb->getQuery()->execute();
        }
    }
}
