<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Composite primary key fields order:
 *  - customerGroup
 *  - priceList
 *  - website
 */
class PriceListToCustomerGroupRepository extends EntityRepository implements PriceListRepositoryInterface
{
    /**
     * @param BasePriceList $priceList
     * @param CustomerGroup $customerGroup
     * @param Website $website
     * @return PriceListToCustomerGroup
     */
    public function findByPrimaryKey(BasePriceList $priceList, CustomerGroup $customerGroup, Website $website)
    {
        return $this->findOneBy(['customerGroup' => $customerGroup, 'priceList' => $priceList, 'website' => $website]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceLists($customerGroup, Website $website, $sortOrder = Criteria::ASC)
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->innerJoin('relation.priceList', 'priceList')
            ->where($qb->expr()->eq('relation.customerGroup', ':customerGroup'))
            ->andWhere($qb->expr()->eq('relation.website', ':website'))
            ->orderBy('relation.sortOrder', QueryBuilderUtil::getSortOrder($sortOrder))
            ->setParameters(['customerGroup' => $customerGroup, 'website' => $website]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Website $website
     * @return BufferedQueryResultIteratorInterface|CustomerGroup[]
     */
    public function getCustomerGroupIteratorWithDefaultFallback(Website $website)
    {
        $subQb = $this->getEntityManager()->createQueryBuilder();
        $subQb->select('plToCustomerGroup.id')
            ->from(PriceListToCustomerGroup::class, 'plToCustomerGroup')
            ->where(
                $subQb->expr()->andX(
                    $subQb->expr()->eq('plToCustomerGroup.customerGroup', 'customerGroup'),
                    $subQb->expr()->eq('plToCustomerGroup.website', ':website')
                )
            );

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('customerGroup')
            ->from(CustomerGroup::class, 'customerGroup')
            ->leftJoin(
                PriceListCustomerGroupFallback::class,
                'priceListFallBack',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('priceListFallBack.customerGroup', 'customerGroup'),
                    $qb->expr()->eq('priceListFallBack.website', ':website'),
                    $qb->expr()->eq('priceListFallBack.fallback', ':fallback')
                )
            )
            ->where($qb->expr()->isNull('priceListFallBack.fallback'))
            ->andWhere($qb->expr()->exists($subQb->getDQL()))
            ->setParameter('fallback', PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY)
            ->setParameter('website', $website)
            ->orderBy('customerGroup.id', Criteria::ASC);

        return new BufferedIdentityQueryResultIterator($qb->getQuery());
    }

    /**
     * @param Website $website
     * @return BufferedQueryResultIteratorInterface|Website[]
     */
    public function getCustomerGroupIteratorWithSelfFallback(Website $website)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('customerGroup')
            ->from(CustomerGroup::class, 'customerGroup')
            ->innerJoin(
                PriceListCustomerGroupFallback::class,
                'priceListFallBack',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('priceListFallBack.customerGroup', 'customerGroup'),
                    $qb->expr()->eq('priceListFallBack.website', ':website')
                )
            )
            ->where(
                $qb->expr()->eq('priceListFallBack.fallback', ':websiteFallback')
            )
            ->setParameter('websiteFallback', PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY)
            ->setParameter('website', $website);

        return new BufferedIdentityQueryResultIterator($qb->getQuery());
    }

    /**
     * @return int[]
     */
    public function getAllWebsiteIds()
    {
        return array_column($this->createQueryBuilder('pltcg')
            ->select('DISTINCT IDENTITY(pltcg.website) AS websiteId')
            ->getQuery()
            ->getResult(), 'websiteId');
    }

    /**
     * @param PriceList $priceList
     *
     * @return BufferedQueryResultIteratorInterface Each item is an array with the following properties:
     *                                              customerGroup - contains customer group ID
     *                                              website - contains website ID
     */
    public function getIteratorByPriceList(PriceList $priceList)
    {
        return $this->getIteratorByPriceLists([$priceList]);
    }

    /**
     * @param PriceList[] $priceLists
     *
     * @return BufferedQueryResultIteratorInterface Each item is an array with the following properties:
     *                                              customerGroup - contains customer group ID
     *                                              website - contains website ID
     */
    public function getIteratorByPriceLists($priceLists)
    {
        $qb = $this->createQueryBuilder('PriceListToCustomerGroup');

        $qb->select(
            'IDENTITY(PriceListToCustomerGroup.customerGroup) as customerGroup',
            'IDENTITY(PriceListToCustomerGroup.website) as website'
        )
            ->where($qb->expr()->in('PriceListToCustomerGroup.priceList', ':priceLists'))
            ->groupBy('PriceListToCustomerGroup.customerGroup', 'PriceListToCustomerGroup.website')
            ->setParameter('priceLists', $priceLists)
            // order required for BufferedIdentityQueryResultIterator on PostgreSql
            ->orderBy('PriceListToCustomerGroup.customerGroup, PriceListToCustomerGroup.website');

        return new BufferedQueryResultIterator($qb);
    }

    public function hasRelationWithPriceList(PriceList $priceList): bool
    {
        $qb = $this->createQueryBuilder('priceListToCustomerGroup');
        $qb
            ->select('priceListToCustomerGroup.id')
            ->where('priceListToCustomerGroup.priceList = :priceList')
            ->setParameter('priceList', $priceList)
            ->setMaxResults(1);

        return (bool)$qb->getQuery()->getScalarResult();
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param Website $website
     * @return mixed
     */
    public function delete(CustomerGroup $customerGroup, Website $website)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'PriceListToCustomerGroup')
            ->where('PriceListToCustomerGroup.customerGroup = :customerGroup')
            ->andWhere('PriceListToCustomerGroup.website = :website')
            ->setParameter('customerGroup', $customerGroup)
            ->setParameter('website', $website)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array|CustomerGroup[]|int[] $holdersIds
     * @return PriceListToCustomerGroup[]
     */
    public function getRelationsByHolders(array $holdersIds)
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->addSelect('partial website.{id, name}')
            ->addSelect('partial priceList.{id, name}')
            ->leftJoin('relation.website', 'website')
            ->leftJoin('relation.priceList', 'priceList')
            ->where($qb->expr()->in('relation.customerGroup', ':groups'))
            ->orderBy('relation.customerGroup')
            ->addOrderBy('relation.website')
            ->addOrderBy('relation.sortOrder')
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
        QueryBuilderUtil::checkIdentifier($parameterName);
        $parentAlias = $queryBuilder->getRootAliases()[0];

        $subQueryBuilder = $this->createQueryBuilder('relation');
        $subQueryBuilder->where(
            $subQueryBuilder->expr()->andX(
                $subQueryBuilder->expr()->eq('relation.customerGroup', $parentAlias),
                $subQueryBuilder->expr()->eq('relation.priceList', ':' . $parameterName)
            )
        );

        $queryBuilder->andWhere($subQueryBuilder->expr()->exists($subQueryBuilder->getQuery()->getDQL()));
        $queryBuilder->setParameter($parameterName, $priceList);
    }

    public function hasAssignedPriceLists(Website $website, CustomerGroup $customerGroup): bool
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select('p.id')
            ->where($qb->expr()->eq('p.website', ':website'))
            ->andWhere($qb->expr()->eq('p.customerGroup', ':customerGroup'))
            ->setParameter('website', $website)
            ->setParameter('customerGroup', $customerGroup)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult() !== null;
    }

    public function getFirstRelation(Website $website, CustomerGroup $customerGroup): ?PriceListToCustomerGroup
    {
        $qb = $this->createQueryBuilder('rel');
        $qb->where($qb->expr()->eq('rel.customerGroup', ':customerGroup'))
            ->andWhere($qb->expr()->eq('rel.website', ':website'))
            ->setParameter('customerGroup', $customerGroup)
            ->setParameter('website', $website)
            ->orderBy('rel.sortOrder')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
