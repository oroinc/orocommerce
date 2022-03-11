<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Repository for ORM entity CombinedPriceListToWebsite
 */
class CombinedPriceListToWebsiteRepository extends PriceListToWebsiteRepository
{
    use BasicCombinedRelationRepositoryTrait;

    public function deleteInvalidRelations()
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->select('relation')
            ->leftJoin(
                PriceListWebsiteFallback::class,
                'fallback',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('fallback.website', 'relation.website'),
                    $qb->expr()->eq('fallback.fallback', ':fallback'),
                )
            )
            ->leftJoin(
                PriceListToWebsite::class,
                'baseRelation',
                Join::WITH,
                $qb->expr()->eq('relation.website', 'baseRelation.website')
            )
            ->where($qb->expr()->isNull('baseRelation.website'))
            ->andWhere($qb->expr()->isNull('fallback.id'))
            ->setParameter('fallback', PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY);

        $this->deleteInvalidRelationsByQueryBuilder($qb);
    }

    public function getWebsitesByCombinedPriceList(CombinedPriceList $combinedPriceList): array
    {
        $subQb = $this->createQueryBuilder('relation')
            ->select('relation.id')
            ->where('relation.priceList = :priceList')
            ->andWhere('relation.website = website');

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('website')
            ->from(Website::class, 'website')
            ->where($qb->expr()->exists($subQb->getDQL()))
            ->setParameter('priceList', $combinedPriceList);

        return $qb->getQuery()->getResult();
    }
}
