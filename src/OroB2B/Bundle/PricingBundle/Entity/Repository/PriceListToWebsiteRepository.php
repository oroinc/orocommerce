<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
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
        return $this->createQueryBuilder('PriceListToWebsite')
            ->innerJoin('PriceListToWebsite.priceList', 'priceList')
            ->where('PriceListToWebsite.website = :website')
            ->orderBy('PriceListToWebsite.priority', Criteria::DESC)
            ->setParameter('website', $website)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return BufferedQueryResultIterator|PriceListToWebsite[]
     */
    public function getPriceListToWebsiteIterator()
    {
        $qb = $this->createQueryBuilder('plToWebsite');
        $qb->innerJoin('plToWebsite.website', 'website')
            ->innerJoin(
                'OroB2BPricingBundle:PriceListAccountGroupFallback',
                'priceListFallBack',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('plToWebsite.website', 'priceListFallBack.website'),
                    $qb->expr()->eq('priceListFallBack.fallback', ':fallbackToWebsite')
                )
            )
            ->andWhere('priceListFallBack.fallback = :fallbackToWebsite')
            ->groupBy('plToWebsite.website')
            ->orderBy('plToWebsite.website')
            ->setParameter('fallbackToWebsite', PriceListWebsiteFallback::CONFIG);

        $iterator = new BufferedQueryResultIterator($qb->getQuery());

        return $iterator;
    }
}
