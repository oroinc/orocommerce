<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListRepository extends EntityRepository
{
    /**
     * @todo: should be dropped in scope of BB-1858
     */
    protected function dropDefaults()
    {
        $qb = $this->createQueryBuilder('pl');

        $qb
            ->update()
            ->set('pl.default', ':defaultValue')
            ->setParameter('defaultValue', false)
            ->where($qb->expr()->eq('pl.default', ':oldValue'))
            ->setParameter('oldValue', true)
            ->getQuery()
            ->execute();
    }

    /**
     * @todo: should be dropped in scope of BB-1858
     * @param PriceList $priceList
     */
    public function setDefault(PriceList $priceList)
    {
        $this->dropDefaults();

        $qb = $this->createQueryBuilder('pl');

        $qb
            ->update()
            ->set('pl.default', ':newValue')
            ->setParameter('newValue', true)
            ->where($qb->expr()->eq('pl', ':entity'))
            ->setParameter('entity', $priceList)
            ->getQuery()
            ->execute();
    }

    /**
     * @todo: should be dropped in scope of BB-1858
     * @return PriceList
     */
    public function getDefault()
    {
        $qb = $this->createQueryBuilder('pl');

        return $qb
            ->where($qb->expr()->eq('pl.default', ':default'))
            ->setParameter('default', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @todo remove in scope of BB-1851
     * @param Account $account
     * @return PriceList|null
     */
    public function getPriceListByAccount(Account $account)
    {
        // TODO: need to refactor this method later because account might have several related price lists
        // TODO: also need to move this method to appropriate repository
        return $this->createQueryBuilder('priceList')
            ->innerJoin(
                'OroB2BPricingBundle:PriceListToAccount',
                'priceListToAccount',
                Join::WITH,
                'priceListToAccount.priceList = priceList'
            )
            ->innerJoin('priceListToAccount.account', 'account')
            ->andWhere('account = :account')
            ->setParameter('account', $account)
            ->orderBy('priceList.id')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @todo remove in scope of BB-1851
     * @param AccountGroup $accountGroup
     * @return PriceList|null
     */
    public function getPriceListByAccountGroup(AccountGroup $accountGroup)
    {
        // TODO: need to refactor this method later because account group might have several related price lists
        // TODO: also need to move this method to appropriate repository
        return $this->createQueryBuilder('priceList')
            ->innerJoin(
                'OroB2BPricingBundle:PriceListToAccountGroup',
                'priceListToAccountGroup',
                Join::WITH,
                'priceListToAccountGroup.priceList = priceList'
            )
            ->innerJoin('priceListToAccountGroup.accountGroup', 'accountGroup')
            ->andWhere('accountGroup = :accountGroup')
            ->setParameter('accountGroup', $accountGroup)
            ->orderBy('priceList.id')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @todo remove in scope of BB-1851
     * @param Website $website
     * @return PriceList|null
     */
    public function getPriceListByWebsite(Website $website)
    {
        // TODO: need to refactor this method later because website might have several related price lists
        // TODO: also need to move this method to appropriate repository
        return $this->createQueryBuilder('priceList')
            ->innerJoin(
                'OroB2BPricingBundle:PriceListToWebsite',
                'priceListToWebsite',
                Join::WITH,
                'priceListToWebsite.priceList = priceList'
            )
            ->innerJoin('priceListToWebsite.website', 'website')
            ->andWhere('website = :website')
            ->setParameter('website', $website)
            ->orderBy('priceList.id')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
