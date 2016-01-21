<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
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
     * {@inheritdoc}
     */
    public function getPriceLists($account, Website $website, $order = Criteria::DESC)
    {
        return $this->createQueryBuilder('PriceListToAccount')
            ->innerJoin('PriceListToAccount.priceList', 'priceList')
            ->innerJoin('PriceListToAccount.account', 'account')
            ->where('account = :account')
            ->andWhere('PriceListToAccount.website = :website')
            ->orderBy('PriceListToAccount.priority', $order)
            ->setParameters(['account' => $account, 'website' => $website])
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @param int $fallback
     * @return BufferedQueryResultIterator|Account[]
     */
    public function getAccountIteratorByFallback(AccountGroup $accountGroup, Website $website, $fallback)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('distinct account')
            ->from('OroB2BAccountBundle:Account', 'account');

        $qb->innerJoin(
            'OroB2BPricingBundle:PriceListToAccount',
            'plToAccount',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('plToAccount.website', ':website'),
                $qb->expr()->eq('plToAccount.account', 'account')
            )
        );
        $qb->innerJoin(
            'OroB2BPricingBundle:PriceListAccountFallback',
            'priceListFallBack',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('priceListFallBack.website', ':website'),
                $qb->expr()->eq('priceListFallBack.account', 'account'),
                $qb->expr()->eq('priceListFallBack.fallback', ':fallbackToGroup')
            )
        );
        $qb->andWhere('account.group = :accountGroup')
            ->setParameter('accountGroup', $accountGroup)
            ->setParameter('fallbackToGroup', $fallback)
            ->setParameter('website', $website);

        return new BufferedQueryResultIterator($qb->getQuery());
    }
}
