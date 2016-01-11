<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
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
     * @param BasePriceList $priceList
     * @param Account $account
     * @param Website $website
     * @return PriceListToAccount
     */
    public function findByPrimaryKey(BasePriceList $priceList, Account $account, Website $website)
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
            ->orderBy('PriceListToAccount.priority', Criteria::DESC)
            ->setParameters(['account' => $account, 'website' => $website])
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @return BufferedQueryResultIterator
     */
    public function getPriceListToAccountByWebsiteIterator(AccountGroup $accountGroup, Website $website)
    {
        $qb = $this->createQueryBuilder('plToAccount');
        $qb->innerJoin('plToAccount.account', 'account')
            ->innerJoin(
                'OroB2BPricingBundle:PriceListAccountGroupFallback',
                'priceListFallBack',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('plToAccount.website', 'priceListFallBack.website'),
                    $qb->expr()->eq('plToAccount.account', 'priceListFallBack.account'),
                    $qb->expr()->eq('priceListFallBack.fallback', ':fallbackToWebsite')
                )
            )
            ->where('plToAccount.website = :website')
            ->andWhere('account.group = :accountGroup')
            ->orderBy('account.id')
            ->setParameter('accountGroup', $accountGroup)
            ->setParameter('fallbackToWebsite', PriceListAccountFallback::ACCOUNT_GROUP)
            ->setParameter('website', $website);

        $iterator = new BufferedQueryResultIterator($qb->getQuery());

        return $iterator;
    }
}
