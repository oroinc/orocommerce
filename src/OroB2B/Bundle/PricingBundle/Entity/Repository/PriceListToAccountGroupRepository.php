<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - accountGroup
 *  - priceList
 *  - website
 */
class PriceListToAccountGroupRepository extends EntityRepository implements PriceListRepositoryInterface
{
    /**
     * @param PriceList $priceList
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @return PriceListToAccountGroup
     */
    public function findByPrimaryKey(PriceList $priceList, AccountGroup $accountGroup, Website $website)
    {
        return $this->findOneBy(['accountGroup' => $accountGroup, 'priceList' => $priceList, 'website' => $website]);
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @return PriceListToAccountGroup[]
     */
    public function getPriceLists($accountGroup, Website $website)
    {
        return $this->createQueryBuilder('PriceListToAccountGroup')
            ->innerJoin('PriceListToAccountGroup.priceList', 'priceList')
            ->innerJoin('PriceListToAccountGroup.accountGroup', 'accountGroup')
            ->where('accountGroup = :accountGroup')
            ->andWhere('PriceListToAccountGroup.website = :website')
            ->orderBy('PriceListToAccountGroup.priority', Criteria::DESC)
            ->setParameters(['accountGroup' => $accountGroup, 'website' => $website])
            ->getQuery()
            ->getResult();
    }
}
