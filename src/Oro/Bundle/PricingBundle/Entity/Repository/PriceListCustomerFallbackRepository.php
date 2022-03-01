<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;

/**
 * Repository for PriceListCustomerFallback entity
 */
class PriceListCustomerFallbackRepository extends EntityRepository
{
    public function getCustomerIdentityByGroup(array $customerGroups, int $websiteId): \Iterator
    {
        if (empty($customerGroups)) {
            return new \ArrayIterator([]);
        }
        $qb = $this->getBaseQbForFallback($websiteId);

        $qb->andWhere($qb->expr()->in('customer.group', ':groups'))
            ->setParameter('groups', $customerGroups);

        $iterator = new BufferedIdentityQueryResultIterator($qb);
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);

        return $iterator;
    }

    public function getBaseQbForFallback(int $websiteId): QueryBuilder
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('DISTINCT customer.id')
            ->from(Customer::class, 'customer')
            ->leftJoin(
                PriceListCustomerFallback::class,
                'customerFallback',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('customer.id', 'customerFallback.customer'),
                    $qb->expr()->eq('customerFallback.website', ':website')
                )
            )
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('customerFallback.id'),
                    $qb->expr()->eq('customerFallback.fallback', ':fallback')
                )
            )
            ->setParameter('website', $websiteId, Types::INTEGER)
            ->setParameter('fallback', PriceListCustomerFallback::ACCOUNT_GROUP, Types::INTEGER);

        return $qb;
    }
}
