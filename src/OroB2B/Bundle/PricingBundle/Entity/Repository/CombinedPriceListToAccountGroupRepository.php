<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;

class CombinedPriceListToAccountGroupRepository extends PriceListToAccountGroupRepository
{
    use BasicCombinedRelationRepositoryTrait;

    public function deleteInvalidRelations()
    {
        //TODO: remove multi-column PK, delete relations by condition IN(:ids)
        $qb = $this->createQueryBuilder('relation');
        $qb->select('relation')
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
        $invalidRelations = $qb->getQuery()->getResult();
        if ($invalidRelations) {
            $manager = $this->getEntityManager();
            foreach ($invalidRelations as $relation) {
                $manager->remove($relation);
            }
            $manager->flush();
        }
    }
}
