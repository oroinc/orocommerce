<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
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
     * {@inheritdoc}
     */
    public function getPriceLists($accountGroup, Website $website, $sortOrder = Criteria::DESC)
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->innerJoin('relation.priceList', 'priceList')
            ->where($qb->expr()->eq('relation.accountGroup', ':accountGroup'))
            ->andWhere($qb->expr()->eq('relation.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.active', ':active'))
            ->orderBy('relation.priority', $sortOrder)
            ->setParameters(['accountGroup' => $accountGroup, 'website' => $website, 'active' => true]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Website $website
     * @param int|null $fallback
     * @return BufferedQueryResultIterator|AccountGroup[]
     */
    public function getAccountGroupIteratorByDefaultFallback(Website $website, $fallback = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('distinct accountGroup')
            ->from('OroB2BAccountBundle:AccountGroup', 'accountGroup');

        $qb->innerJoin(
            'OroB2BPricingBundle:PriceListToAccountGroup',
            'plToAccountGroup',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('plToAccountGroup.accountGroup', 'accountGroup'),
                $qb->expr()->eq('plToAccountGroup.website', ':website')
            )
        );

        $qb->leftJoin(
            'OroB2BPricingBundle:PriceListAccountGroupFallback',
            'priceListFallBack',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('priceListFallBack.accountGroup', 'accountGroup'),
                $qb->expr()->eq('priceListFallBack.website', ':website')
            )
        )
        ->setParameter('website', $website)
        ->orderBy('accountGroup.id', Criteria::ASC);

        if ($fallback !== null) {
            $qb->where(
                $qb->expr()->orX(
                    $qb->expr()->eq('priceListFallBack.fallback', ':fallbackToWebsite'),
                    $qb->expr()->isNull('priceListFallBack.fallback')
                )
            )
                ->setParameter('fallbackToWebsite', $fallback);
        }

        return new BufferedQueryResultIterator($qb->getQuery());
    }

    /**
     * @param AccountGroup $accountGroup
     * @return int[]
     */
    public function getWebsiteIdsByAccountGroup(AccountGroup $accountGroup)
    {
        $qb = $this->createQueryBuilder('PriceListToAccountGroup');

        $result = $qb->select('distinct(PriceListToAccountGroup.website)')
            ->andWhere($qb->expr()->eq('PriceListToAccountGroup.accountGroup', ':accountGroup'))
            ->setParameter('accountGroup', $accountGroup)
            ->getQuery()
            ->getResult();

        return array_map('current', $result);
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @return mixed
     */
    public function delete(AccountGroup $accountGroup, Website $website)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'PriceListToAccountGroup')
            ->where('PriceListToAccountGroup.accountGroup = :accountGroup')
            ->andWhere('PriceListToAccountGroup.website = :website')
            ->setParameter('accountGroup', $accountGroup)
            ->setParameter('website', $website)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array AccountGroup[]|int[] $holdersIds
     * @return PriceListToAccountGroup[]
     */
    public function getRelationsByHolders(array $holdersIds)
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->addSelect('partial website.{id, name}')
            ->addSelect('partial priceList.{id, name}')
            ->leftJoin('relation.website', 'website')
            ->leftJoin('relation.priceList', 'priceList')
            ->where($qb->expr()->in('relation.accountGroup', ':groups'))
            ->orderBy('relation.accountGroup')
            ->addOrderBy('relation.website')
            ->addOrderBy('relation.priority')
            ->setParameter('groups', $holdersIds);

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
                $subQueryBuilder->expr()->eq('relation.accountGroup', $parentAlias),
                $subQueryBuilder->expr()->eq('relation.priceList', ':' . $parameterName)
            )
        );

        $queryBuilder->andWhere($subQueryBuilder->expr()->exists($subQueryBuilder->getQuery()->getDQL()));
        $queryBuilder->setParameter($parameterName, $priceList);
    }
}
