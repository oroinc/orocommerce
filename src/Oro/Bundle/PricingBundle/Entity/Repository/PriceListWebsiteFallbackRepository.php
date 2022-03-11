<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
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
    public function getWebsiteIdByDefaultFallback(): array
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('website.id')
            ->from(Website::class, 'website')
            ->leftJoin(
                PriceListWebsiteFallback::class,
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
            ->setParameter('fallback', PriceListWebsiteFallback::CONFIG, Types::INTEGER);

        return $qb->getQuery()->getResult(Query::HYDRATE_SCALAR);
    }
}
