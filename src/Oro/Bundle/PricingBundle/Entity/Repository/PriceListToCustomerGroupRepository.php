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
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
            ->andWhere($qb->expr()->eq('priceList.active', ':active'))
            ->orderBy('relation.sortOrder', $sortOrder)
            ->setParameters(['customerGroup' => $customerGroup, 'website' => $website, 'active' => true]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Website $website
     * @param int|null $fallback
     * @return BufferedQueryResultIteratorInterface|CustomerGroup[]
     */
    public function getCustomerGroupIteratorByDefaultFallback(Website $website, $fallback = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('distinct customerGroup')
            ->from('OroCustomerBundle:CustomerGroup', 'customerGroup');

        $qb->innerJoin(
            'OroPricingBundle:PriceListToCustomerGroup',
            'plToCustomerGroup',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('plToCustomerGroup.customerGroup', 'customerGroup'),
                $qb->expr()->eq('plToCustomerGroup.website', ':website')
            )
        );

        $qb->leftJoin(
            'OroPricingBundle:PriceListCustomerGroupFallback',
            'priceListFallBack',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('priceListFallBack.customerGroup', 'customerGroup'),
                $qb->expr()->eq('priceListFallBack.website', ':website')
            )
        )
        ->setParameter('website', $website)
        ->orderBy('customerGroup.id', Criteria::ASC);

        if ($fallback !== null) {
            $qb->where(
                $qb->expr()->orX(
                    $qb->expr()->eq('priceListFallBack.fallback', ':fallbackToWebsite'),
                    $qb->expr()->isNull('priceListFallBack.fallback')
                )
            )
                ->setParameter('fallbackToWebsite', $fallback);
        }

        return new BufferedIdentityQueryResultIterator($qb->getQuery());
    }

    /**
     * @param PriceList $priceList
     * @return BufferedQueryResultIteratorInterface
     */
    public function getIteratorByPriceList(PriceList $priceList)
    {
        $qb = $this->createQueryBuilder('PriceListToCustomerGroup');

        $qb->select(
            sprintf('IDENTITY(PriceListToCustomerGroup.customerGroup) as %s', PriceListRelationTrigger::ACCOUNT_GROUP),
            sprintf('IDENTITY(PriceListToCustomerGroup.website) as %s', PriceListRelationTrigger::WEBSITE)
        )
            ->where('PriceListToCustomerGroup.priceList = :priceList')
            ->groupBy('PriceListToCustomerGroup.customerGroup', 'PriceListToCustomerGroup.website')
            ->setParameter('priceList', $priceList)
            // order required for BufferedIdentityQueryResultIterator on PostgreSql
            ->orderBy('PriceListToCustomerGroup.customerGroup, PriceListToCustomerGroup.website')
        ;

        return new BufferedQueryResultIterator($qb);
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
     * @param array CustomerGroup[]|int[] $holdersIds
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
}
