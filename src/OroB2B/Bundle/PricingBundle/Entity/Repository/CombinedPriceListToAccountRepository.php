<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;

class CombinedPriceListToAccountRepository extends PriceListToAccountRepository
{
    use BasicCombinedRelationRepositoryTrait;

    public function deleteInvalidRelations()
    {
        //TODO: remove multi-column PK, delete relations by condition IN(:ids)
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
