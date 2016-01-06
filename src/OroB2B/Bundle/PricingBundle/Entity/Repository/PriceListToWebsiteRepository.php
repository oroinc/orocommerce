<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
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
     * @param PriceList $priceList
     * @param Website $website
     * @return PriceListToWebsite
     */
    public function findByPrimaryKey(PriceList $priceList, Website $website)
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
            ->setParameter('website', $website)
            ->orderBy('PriceListToWebsite.priority')
            ->getQuery()
            ->getResult();
    }
}
