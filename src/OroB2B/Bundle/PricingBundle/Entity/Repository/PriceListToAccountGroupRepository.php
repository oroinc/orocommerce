<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
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
     * @param BasePriceList $priceList
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @return PriceListToAccountGroup
     */
    public function findByPrimaryKey(BasePriceList $priceList, AccountGroup $accountGroup, Website $website)
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

    /**
     * @param Website $website
     * @return BufferedQueryResultIterator
     */
    public function getPriceListToAccountGroupByWebsiteIterator(Website $website)
    {
        $qb = $this->createQueryBuilder('plToAccountGroup');
        $qb->innerJoin(
            'OroB2BPricingBundle:PriceListAccountGroupFallback',
            'priceListFallBack',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('plToAccountGroup.website', 'priceListFallBack.website'),
                $qb->expr()->eq('plToAccountGroup.accountGroup', 'priceListFallBack.accountGroup'),
                $qb->expr()->eq('priceListFallBack.fallback', ':fallbackToWebsite')
            )
        )
        ->where($qb->expr()->eq('plToAccountGroup.website', ':website'))
        ->setParameter('fallbackToWebsite', PriceListAccountGroupFallback::WEBSITE)
        ->setParameter('website', $website);

        $iterator = new BufferedQueryResultIterator($qb->getQuery());

        return $iterator;
    }
}
