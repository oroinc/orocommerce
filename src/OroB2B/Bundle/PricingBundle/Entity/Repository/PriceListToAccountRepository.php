<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - account
 *  - priceList
 *  - website
 */
class PriceListToAccountRepository extends EntityRepository implements PriceListRepositoryInterface
{
    /**
     * @param PriceList $priceList
     * @param Account $account
     * @param Website $website
     * @return PriceListToAccount
     */
    public function findByPrimaryKey(PriceList $priceList, Account $account, Website $website)
    {
        return $this->findOneBy(['account' => $account, 'priceList' => $priceList, 'website' => $website]);
    }

    /**
     * @param Account $account
     * @param Website $website
     * @return PriceListToAccount[]
     */
    public function getPriceLists($account, Website $website)
    {
        return $this->createQueryBuilder('PriceListToAccount')
            ->innerJoin('PriceListToAccount.priceList', 'priceList')
            ->innerJoin('PriceListToAccount.account', 'account')
            ->where('account = :account')
            ->andWhere('PriceListToAccount.website = :website')
            ->setParameters(['account' => $account, 'website' => $website])
            ->getQuery()
            ->getResult();
    }
}
