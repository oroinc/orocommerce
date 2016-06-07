<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;

class PriceListWebsiteFallbackRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function getWebsiteIdByDefaultFallback()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('website.id')
            ->from('OroB2BWebsiteBundle:Website', 'website')
            ->leftJoin(
                'OroB2BPricingBundle:PriceListWebsiteFallback',
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
}
