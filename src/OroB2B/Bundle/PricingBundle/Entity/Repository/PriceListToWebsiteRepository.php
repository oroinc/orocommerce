<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - priceList
 *  - website
 */
class PriceListToWebsiteRepository extends EntityRepository
{
    /**
     * @param BasePriceList $priceList
     * @param Website $website
     * @return PriceListToWebsite
     */
    public function findByPrimaryKey(BasePriceList $priceList, Website $website)
    {
        return $this->findOneBy(['priceList' => $priceList, 'website' => $website]);
    }

    /**
     * @param Website $website
     * @return PriceListToWebsite[]
     */
    public function getPriceLists(Website $website)
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->innerJoin('relation.priceList', 'priceList')
            ->where($qb->expr()->eq('relation.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.active', ':active'))
            ->orderBy('relation.priority', Criteria::DESC)
            ->setParameter('website', $website)
            ->setParameter('active', true);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $fallback
     * @return BufferedQueryResultIterator|Website[]
     */
    public function getWebsiteIteratorByDefaultFallback($fallback)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('distinct website')
            ->from('OroB2BWebsiteBundle:Website', 'website');

        $qb->innerJoin(
            'OroB2BPricingBundle:PriceListToWebsite',
            'plToWebsite',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('plToWebsite.website', 'website')
            )
        );

        if ($fallback !== null) {
            $qb->leftJoin(
                'OroB2BPricingBundle:PriceListWebsiteFallback',
                'priceListFallBack',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('priceListFallBack.website', 'website')
                )
            )
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->eq('priceListFallBack.fallback', ':websiteFallback'),
                    $qb->expr()->isNull('priceListFallBack.fallback')
                )
            )
            ->setParameter('websiteFallback', $fallback);
        }

        return new BufferedQueryResultIterator($qb->getQuery());
    }

    /**
     * @param Website $website
     * @return mixed
     */
    public function delete(Website $website)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'PriceListToWebsite')
            ->andWhere('PriceListToWebsite.website = :website')
            ->setParameter('website', $website)
            ->getQuery()
            ->execute();
    }
}
