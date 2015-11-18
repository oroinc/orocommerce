<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListToAccountGroupRepository extends EntityRepository implements PriceListRepositoryInterface
{
    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @return PriceList[]
     */
    public function getPriceLists($accountGroup, Website $website)
    {
        return $this->createQueryBuilder('PriceListToAccountGroup')
            ->innerJoin('PriceListToAccountGroup.priceList', 'priceList')
            ->innerJoin('PriceListToAccountGroup.accountGroup', 'accountGroup')
            ->where('accountGroup = :accountGroup')
            ->andWhere('PriceListToAccountGroup.website = :website')
            ->setParameters(['accountGroup' => $accountGroup, 'website' => $website])
            ->getQuery()
            ->getResult();
    }
}
