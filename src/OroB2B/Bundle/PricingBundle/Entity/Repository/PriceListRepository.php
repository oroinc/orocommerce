<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

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
            ->innerJoin('priceList.accounts', 'account')
            ->andWhere('account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Account $account
     * @param PriceList $priceList
     */
    public function setPriceListToAccount(Account $account, PriceList $priceList = null)
    {
        $oldPriceList = $this->getPriceListByAccount($account);

        if ($oldPriceList && $priceList && $oldPriceList->getId() === $priceList->getId()) {
            return;
        }

        if ($oldPriceList) {
            $oldPriceList->removeAccount($account);
        }

        if ($priceList) {
            $priceList->addAccount($account);
        }
    }

    /**
     * @param AccountGroup $accountGroup
     * @return PriceList|null
     */
    public function getPriceListByAccountGroup(AccountGroup $accountGroup)
    {
        return $this->createQueryBuilder('priceList')
            ->innerJoin('priceList.accountGroups', 'accountGroup')
            ->andWhere('accountGroup = :accountGroup')
            ->setParameter('accountGroup', $accountGroup)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param AccountGroup $accountGroup
     * @param PriceList $priceList
     */
    public function setPriceListToAccountGroup(AccountGroup $accountGroup, PriceList $priceList = null)
    {
        $oldPriceList = $this->getPriceListByAccountGroup($accountGroup);

        if ($oldPriceList && $priceList && $oldPriceList->getId() === $priceList->getId()) {
            return;
        }

        if ($oldPriceList) {
            $oldPriceList->removeAccountGroup($accountGroup);
        }

        if ($priceList) {
            $priceList->addAccountGroup($accountGroup);
        }
    }

    /**
     * @param Website $website
     * @return PriceList|null
     */
    public function getPriceListByWebsite(Website $website)
    {
        return $this->createQueryBuilder('priceList')
            ->innerJoin('priceList.websites', 'website')
            ->andWhere('website = :website')
            ->setParameter('website', $website)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Website $website
     * @param PriceList $priceList
     */
    public function setPriceListToWebsite(Website $website, PriceList $priceList = null)
    {
        $oldPriceList = $this->getPriceListByWebsite($website);

        if ($oldPriceList && $priceList && $oldPriceList->getId() === $priceList->getId()) {
            return;
        }

        if ($oldPriceList) {
            $oldPriceList->removeWebsite($website);
        }

        if ($priceList) {
            $priceList->addWebsite($website);
        }
    }
}
