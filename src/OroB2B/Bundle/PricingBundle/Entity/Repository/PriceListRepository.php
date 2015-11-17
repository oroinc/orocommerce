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
     * @param Account $account
     * @return PriceList|null
     */
    public function getPriceListByAccount(Account $account)
    {
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
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param AccountGroup $accountGroup
     * @return PriceList|null
     */
    public function getPriceListByAccountGroup(AccountGroup $accountGroup)
    {
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
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Website $website
     * @return PriceList|null
     */
    public function getPriceListByWebsite(Website $website)
    {
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
            ->getQuery()
            ->getOneOrNullResult();
    }
}
