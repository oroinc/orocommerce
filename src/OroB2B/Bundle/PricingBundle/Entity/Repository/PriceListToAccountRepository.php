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
    public function getPriceLists($account, Website $website, $sortOrder = Criteria::DESC)
    {
        return $this->createQueryBuilder('PriceListToAccount')
            ->innerJoin('PriceListToAccount.priceList', 'priceList')
            ->innerJoin('PriceListToAccount.account', 'account')
            ->where('account = :account')
            ->andWhere('PriceListToAccount.website = :website')
            ->orderBy('PriceListToAccount.priority', $sortOrder)
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
    public function getAccountIteratorByDefaultFallback(AccountGroup $accountGroup, Website $website, $fallback)
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
        $qb->leftJoin(
            'OroB2BPricingBundle:PriceListAccountFallback',
            'priceListFallBack',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('priceListFallBack.website', ':website'),
                $qb->expr()->eq('priceListFallBack.account', 'account')
            )
        );
        $qb->andWhere($qb->expr()->eq('account.group', ':accountGroup'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('priceListFallBack.fallback', ':fallbackToGroup'),
                    $qb->expr()->isNull('priceListFallBack.fallback')
                )
            )
            ->setParameter('accountGroup', $accountGroup)
            ->setParameter('fallbackToGroup', $fallback)
            ->setParameter('website', $website);

        return new BufferedQueryResultIterator($qb->getQuery());
    }

    /**
     * @param Account $account
     * @param Website $website
     * @return mixed
     */
    public function delete(Account $account, Website $website)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'PriceListToAccount')
            ->where('PriceListToAccount.account = :account')
            ->andWhere('PriceListToAccount.website = :website')
            ->setParameter('account', $account)
            ->setParameter('website', $website)
            ->getQuery()
            ->execute();
    }
}
