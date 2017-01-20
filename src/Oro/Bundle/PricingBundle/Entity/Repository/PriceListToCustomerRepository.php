<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Model\DTO\CustomerWebsiteDTO;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - customer
 *  - priceList
 *  - website
 */
class PriceListToCustomerRepository extends EntityRepository implements PriceListRepositoryInterface
{
    /**
     * @param BasePriceList $priceList
     * @param Customer $customer
     * @param Website $website
     * @return PriceListToCustomer
     */
    public function findByPrimaryKey(BasePriceList $priceList, Customer $customer, Website $website)
    {
        return $this->findOneBy(['customer' => $customer, 'priceList' => $priceList, 'website' => $website]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceLists($customer, Website $website, $sortOrder = Criteria::ASC)
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->innerJoin('relation.priceList', 'priceList')
            ->where($qb->expr()->eq('relation.customer', ':customer'))
            ->andWhere($qb->expr()->eq('relation.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.active', ':active'))
            ->orderBy('relation.priority', $sortOrder)
            ->setParameters(['customer' => $customer, 'website' => $website, 'active' => true]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param Website $website
     * @param int|null $fallback
     * @return BufferedQueryResultIterator|Customer[]
     */
    public function getCustomerIteratorByDefaultFallback(
        CustomerGroup $customerGroup,
        Website $website,
        $fallback = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('distinct customer')
            ->from('OroCustomerBundle:Customer', 'customer');

        $qb->innerJoin(
            PriceListToCustomer::class,
            'plToCustomer',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('plToCustomer.website', ':website'),
                $qb->expr()->eq('plToCustomer.customer', 'customer')
            )
        );

        $qb->leftJoin(
            PriceListCustomerFallback::class,
            'priceListFallBack',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('priceListFallBack.website', ':website'),
                $qb->expr()->eq('priceListFallBack.customer', 'customer')
            )
        )
            ->setParameter('website', $website);

        $qb->andWhere($qb->expr()->eq('customer.group', ':customerGroup'))
            ->setParameter('customerGroup', $customerGroup);

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
     * @param CustomerGroup $customerGroup
     * @return BufferedQueryResultIterator
     */
    public function getCustomerWebsitePairsByCustomerGroupIterator(CustomerGroup $customerGroup)
    {
        $qb = $this->createQueryBuilder('PriceListToCustomer');

        $qb->select(
            sprintf('IDENTITY(PriceListToCustomer.customer) as %s', PriceListRelationTrigger::ACCOUNT),
            sprintf('IDENTITY(PriceListToCustomer.website) as %s', PriceListRelationTrigger::WEBSITE)
        )
            ->innerJoin('PriceListToCustomer.customer', 'acc')
            ->innerJoin(
                PriceListToCustomerGroup::class,
                'PriceListToCustomerGroup',
                Join::WITH,
                $qb->expr()->andX(
                    'PriceListToCustomerGroup.customerGroup = acc.group',
                    'PriceListToCustomerGroup.website = PriceListToCustomer.website'
                )
            )
            ->where($qb->expr()->eq('acc.group', ':customerGroup'))
            ->groupBy('PriceListToCustomer.customer', 'PriceListToCustomer.website')
            ->setParameter('customerGroup', $customerGroup);

        return new BufferedQueryResultIterator($qb);
    }

    /**
     * @param PriceList $priceList
     * @return BufferedQueryResultIterator
     */
    public function getIteratorByPriceList(PriceList $priceList)
    {
        $qb = $this->createQueryBuilder('priceListToCustomer');

        $qb->select(
            sprintf('IDENTITY(priceListToCustomer.customer) as %s', PriceListRelationTrigger::ACCOUNT),
            sprintf('IDENTITY(acc.group) as %s', PriceListRelationTrigger::ACCOUNT_GROUP),
            sprintf('IDENTITY(priceListToCustomer.website) as %s', PriceListRelationTrigger::WEBSITE)
        )
            ->leftJoin('priceListToCustomer.customer', 'acc')
            ->where('priceListToCustomer.priceList = :priceList')
            ->groupBy('priceListToCustomer.customer', 'acc.group', 'priceListToCustomer.website')
            ->setParameter('priceList', $priceList);


        return new BufferedQueryResultIterator($qb);
    }

    /**
     * @param Customer $customer
     * @return CustomerWebsiteDTO[]|ArrayCollection
     */
    public function getCustomerWebsitePairsByCustomer(Customer $customer)
    {
        $qb = $this->createQueryBuilder('PriceListToCustomer');

        $pairs = $qb->select(
            'IDENTITY(PriceListToCustomer.customer) as customer_id',
            'IDENTITY(PriceListToCustomer.website) as website_id'
        )
            ->andWhere($qb->expr()->eq('PriceListToCustomer.customer', ':customer'))
            ->groupBy('PriceListToCustomer.customer', 'PriceListToCustomer.website')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getResult();

        $em = $this->getEntityManager();
        $collection = new ArrayCollection();
        foreach ($pairs as $pair) {
            /** @var Customer $customer */
            $customer = $em->getReference('OroCustomerBundle:Customer', $pair['customer_id']);
            /** @var Website $website */
            $website = $em->getReference('OroWebsiteBundle:Website', $pair['website_id']);
            $collection->add(new CustomerWebsiteDTO($customer, $website));
        }

        return $collection;
    }

    /**
     * @param Customer $customer
     * @param Website $website
     * @return mixed
     */
    public function delete(Customer $customer, Website $website)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'PriceListToCustomer')
            ->where('PriceListToCustomer.customer = :customer')
            ->andWhere('PriceListToCustomer.website = :website')
            ->setParameter('customer', $customer)
            ->setParameter('website', $website)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array Customer[]|int[] $holdersIds
     * @return PriceListToCustomer[]
     */
    public function getRelationsByHolders(array $holdersIds)
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->addSelect('partial website.{id, name}')
            ->addSelect('partial priceList.{id, name}')
            ->leftJoin('relation.website', 'website')
            ->leftJoin('relation.priceList', 'priceList')
            ->where($qb->expr()->in('relation.customer', ':customers'))
            ->orderBy('relation.customer')
            ->addOrderBy('relation.website')
            ->addOrderBy('relation.priority')
            ->setParameter('customers', $holdersIds);

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
                $subQueryBuilder->expr()->eq('relation.customer', $parentAlias),
                $subQueryBuilder->expr()->eq('relation.priceList', ':'.$parameterName)
            )
        );

        $queryBuilder->andWhere($subQueryBuilder->expr()->exists($subQueryBuilder->getQuery()->getDQL()));
        $queryBuilder->setParameter($parameterName, $priceList);
    }
}
