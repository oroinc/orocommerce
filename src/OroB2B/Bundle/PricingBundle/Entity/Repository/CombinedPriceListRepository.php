<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class CombinedPriceListRepository extends EntityRepository
{
    /**
     * @param CombinedPriceList $combinedPriceList
     * @return CombinedPriceListToPriceList[]
     */
    public function getPriceListRelations(CombinedPriceList $combinedPriceList)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('partial cpl.{priceList, mergeAllowed}')
            ->from('OroB2BPricingBundle:CombinedPriceListToPriceList', 'cpl')
            ->where($qb->expr()->eq('cpl.combinedPriceList', ':combinedPriceList'))
            ->setParameter('combinedPriceList', $combinedPriceList)
            ->orderBy('cpl.sortOrder');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Account $account
     * @param Website $website
     * @return null|CombinedPriceList
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCombinedPriceListByAccount(Account $account, Website $website)
    {
        $qb = $this->createQueryBuilder('priceList');
        $qb
            ->innerJoin(
                'OroB2BPricingBundle:CombinedPriceListToAccount',
                'priceListToAccount',
                Join::WITH,
                'priceListToAccount.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToAccount.account', ':account'))
            ->andWhere($qb->expr()->eq('priceListToAccount.website', ':website'))
            ->setParameter('account', $account)
            ->setParameter('website', $website)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param AccountGroup $accountGroup
     * @return CombinedPriceList|null
     */
    public function getCombinedPriceListByAccountGroup(AccountGroup $accountGroup, Website $website)
    {
        $qb = $this->createQueryBuilder('priceList');
        $qb
            ->innerJoin(
                'OroB2BPricingBundle:CombinedPriceListToAccountGroup',
                'priceListToAccountGroup',
                Join::WITH,
                'priceListToAccountGroup.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToAccountGroup.accountGroup', ':accountGroup'))
            ->andWhere($qb->expr()->eq('priceListToAccountGroup.website', ':website'))
            ->setParameter('accountGroup', $accountGroup)
            ->setParameter('website', $website)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Website $website
     * @return CombinedPriceList|null
     */
    public function getCombinedPriceListByWebsite(Website $website)
    {
        $qb = $this->createQueryBuilder('priceList');

        $qb
            ->innerJoin(
                'OroB2BPricingBundle:CombinedPriceListToWebsite',
                'priceListToWebsite',
                Join::WITH,
                'priceListToWebsite.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToWebsite.website', ':website'))
            ->setParameter('website', $website)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
