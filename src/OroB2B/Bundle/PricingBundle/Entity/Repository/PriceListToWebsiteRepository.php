<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListToWebsiteRepository extends EntityRepository
{
    /**
     * @param Account $account
     * @param Website $website
     * @return PriceList[]
     */
    public function getPriceListsByWebsite(Account $account, Website $website)
    {
        return $this->createQueryBuilder('PriceListToWebsite')
            ->innerJoin('PriceListToWebsite.priceList', 'priceList')
            ->innerJoin('PriceListToWebsite.account', 'account')
            ->where('PriceListToWebsite.website = :website')
            ->setParameters(['account' => $account, 'website' => $website])
            ->getQuery()
            ->getResult();
    }
}
