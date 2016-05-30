<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Model\DTO\AccountWebsiteDTO;
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
        $qb = $this->createQueryBuilder('relation');
        $qb->innerJoin('relation.priceList', 'priceList')
            ->where($qb->expr()->eq('relation.account', ':account'))
            ->andWhere($qb->expr()->eq('relation.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.active', ':active'))
            ->orderBy('relation.priority', $sortOrder)
            ->setParameters(['account' => $account, 'website' => $website, 'active' => true]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @param int|null $fallback
     * @return BufferedQueryResultIterator|Account[]
     */
    public function getAccountIteratorByDefaultFallback(AccountGroup $accountGroup, Website $website, $fallback = null)
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
        )
        ->setParameter('website', $website);

        $qb->andWhere($qb->expr()->eq('account.group', ':accountGroup'))
            ->setParameter('accountGroup', $accountGroup);

        if ($fallback !== null) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('priceListFallBack.fallback', ':fallbackToGroup'),
                    $qb->expr()->isNull('priceListFallBack.fallback')
                )
            )
            ->setParameter('fallbackToGroup', $fallback);
        }

        return new BufferedQueryResultIterator($qb->getQuery());
    }

    /**
     * @param AccountGroup $accountGroup
     * @param integer[] $websiteIds
     * @return AccountWebsiteDTO[]|ArrayCollection
     */
    public function getAccountWebsitePairsByAccountGroup(AccountGroup $accountGroup, $websiteIds)
    {
        $pairs = $this->getAccountWebsitePairsByAccountGroupQueryBuilder($accountGroup, $websiteIds)
            ->getQuery()
            ->getResult();
        $em = $this->getEntityManager();
        $entityPair = new ArrayCollection();
        foreach ($pairs as $pair) {
            /** @var Account $account */
            $account = $em->getReference('OroB2BAccountBundle:Account', $pair['account_id']);
            /** @var Website $website */
            $website = $em->getReference('OroB2BWebsiteBundle:Website', $pair['website_id']);
            $entityPair->add(new AccountWebsiteDTO($account, $website));
        }

        return $entityPair;
    }

    /**
     * @param AccountGroup $accountGroup
     * @param integer[] $websiteIds
     * @return QueryBuilder
     */
    public function getAccountWebsitePairsByAccountGroupQueryBuilder(AccountGroup $accountGroup, $websiteIds)
    {
        $qb = $this->createQueryBuilder('PriceListToAccount');

        return $qb->select(
            'IDENTITY(PriceListToAccount.account) as account_id',
            'IDENTITY(PriceListToAccount.website) as website_id'
        )
            ->innerJoin('PriceListToAccount.account', 'account')
            ->andWhere($qb->expr()->eq('account.group', ':accountGroup'))
            ->andWhere($qb->expr()->in('PriceListToAccount.website', ':websiteIds'))
            ->groupBy('PriceListToAccount.account', 'PriceListToAccount.website')
            ->setParameter('accountGroup', $accountGroup)
            ->setParameter('websiteIds', $websiteIds);
    }

    /**
     * @param Account $account
     * @return AccountWebsiteDTO[]|ArrayCollection
     */
    public function getAccountWebsitePairsByAccount(Account $account)
    {
        $qb = $this->createQueryBuilder('PriceListToAccount');

        $pairs = $qb->select(
            'IDENTITY(PriceListToAccount.account) as account_id',
            'IDENTITY(PriceListToAccount.website) as website_id'
        )
            ->andWhere($qb->expr()->eq('PriceListToAccount.account', ':account'))
            ->groupBy('PriceListToAccount.account', 'PriceListToAccount.website')
            ->setParameter('account', $account)
            ->getQuery()
            ->getResult();

        $em = $this->getEntityManager();
        $collection = new ArrayCollection();
        foreach ($pairs as $pair) {
            /** @var Account $account */
            $account = $em->getReference('OroB2BAccountBundle:Account', $pair['account_id']);
            /** @var Website $website */
            $website = $em->getReference('OroB2BWebsiteBundle:Website', $pair['website_id']);
            $collection->add(new AccountWebsiteDTO($account, $website));
        }

        return $collection;
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

    /**
     * @param array Account[]|int[] $holdersIds
     * @return PriceListToAccount[]
     */
    public function getRelationsByHolders(array $holdersIds)
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->addSelect('partial website.{id, name}')
            ->addSelect('partial priceList.{id, name}')
            ->leftJoin('relation.website', 'website')
            ->leftJoin('relation.priceList', 'priceList')
            ->where($qb->expr()->in('relation.account', ':accounts'))
            ->orderBy('relation.account')
            ->addOrderBy('relation.website')
            ->addOrderBy('relation.priority')
            ->setParameter('accounts', $holdersIds);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param BasePriceList $priceList
     * @param string $parameterName
     */
    public function restrictByPriceList(
        QueryBuilder $queryBuilder,
        BasePriceList $priceList,
        $parameterName
    ) {
        $parentAlias = $queryBuilder->getRootAliases()[0];

        $subQueryBuilder = $this->createQueryBuilder('relation');
        $subQueryBuilder->where(
            $subQueryBuilder->expr()->andX(
                $subQueryBuilder->expr()->eq('relation.account', $parentAlias),
                $subQueryBuilder->expr()->eq('relation.priceList', ':' . $parameterName)
            )
        );

        $queryBuilder->andWhere($subQueryBuilder->expr()->exists($subQueryBuilder->getQuery()->getDQL()));
        $queryBuilder->setParameter($parameterName, $priceList);
    }
}
