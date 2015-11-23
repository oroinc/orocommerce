<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListToWebsiteRepository extends EntityRepository
{
    /**
     * @param Website $website
     * @return PriceList[]
     */
    public function getPriceLists(Website $website)
    {
        return $this->createQueryBuilder('PriceListToWebsite')
            ->innerJoin('PriceListToWebsite.priceList', 'priceList')
            ->where('PriceListToWebsite.website = :website')
            ->setParameter('website', $website)
            ->getQuery()
            ->getResult();
    }
}
