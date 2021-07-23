<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Doctrine entity repository for  PriceListWebsiteFallback
 */
class PriceListWebsiteFallbackRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function getWebsiteIdByDefaultFallback()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('website.id')
            ->from('OroWebsiteBundle:Website', 'website')
            ->leftJoin(
                'OroPricingBundle:PriceListWebsiteFallback',
                'fallback',
                Join::WITH,
                $qb->expr()->eq('fallback.website', 'website.id')
            )
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('fallback.id'),
                    $qb->expr()->eq('fallback.fallback', ':fallback')
                )
            )
            ->setParameter('fallback', PriceListWebsiteFallback::CONFIG);

        return $qb->getQuery()->getResult(Query::HYDRATE_SCALAR);
    }

    public function hasFallbackOnNextLevel(Website $website): bool
    {
        $qb = $this->createQueryBuilder('f');
        $qb->select('f.id')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('f.website', ':website'),
                    $qb->expr()->eq('f.fallback', ':fallback')
                )
            )->setParameters([
                'website' => $website,
                'fallback' => PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY
            ])
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult() === null;
    }
}
