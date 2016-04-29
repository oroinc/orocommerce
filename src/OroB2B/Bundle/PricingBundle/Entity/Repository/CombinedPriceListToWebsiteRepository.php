<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;

class CombinedPriceListToWebsiteRepository extends PriceListToWebsiteRepository
{
    use BasicCombinedRelationRepositoryTrait;

    public function deleteInvalidRelations()
    {
        //TODO: remove multi-column PK, delete relations by condition IN(:ids)
        $qb = $this->createQueryBuilder('relation');
        $qb->select('relation')
            ->leftJoin(
                'OroB2BPricingBundle:PriceListToWebsite',
                'baseRelation',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('relation.website', 'baseRelation.website')
                )
            )
            ->where($qb->expr()->isNull('baseRelation.website'));
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
