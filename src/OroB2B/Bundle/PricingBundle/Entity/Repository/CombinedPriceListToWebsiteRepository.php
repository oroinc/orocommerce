<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;

class CombinedPriceListToWebsiteRepository extends PriceListToWebsiteRepository
{
    use BasicCombinedRelationRepositoryTrait;

    public function deleteInvalidRelations()
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->select('relation')
            ->leftJoin(
                'OroB2BPricingBundle:PriceListWebsiteFallback',
                'fallback',
                Join::WITH,
                $qb->expr()->eq('fallback.website', 'relation.website')
            )
            ->leftJoin(
                'OroB2BPricingBundle:PriceListToWebsite',
                'baseRelation',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('relation.website', 'baseRelation.website')
                )
            )
            ->where($qb->expr()->isNull('baseRelation.website'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('fallback.fallback', PriceListWebsiteFallback::CONFIG),
                    $qb->expr()->isNull('fallback.fallback')
                )
            );
        $result = $qb->getQuery()->getScalarResult();
        $invalidRelationIds = array_map('current', $result);
        if ($invalidRelationIds) {
            $qb = $this->createQueryBuilder('relation');
            $qb->delete()->where($qb->expr()->in('relation.id', $invalidRelationIds));
            $qb->getQuery()->execute();
        }
    }
}
