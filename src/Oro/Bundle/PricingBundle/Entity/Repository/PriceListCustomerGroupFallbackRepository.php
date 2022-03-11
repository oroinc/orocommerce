<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;

/**
 * Repository for PriceListCustomerGroupFallback entity
 */
class PriceListCustomerGroupFallbackRepository extends EntityRepository
{
    public function getCustomerIdentityByWebsite(int $websiteId): \Iterator
    {
        /** @var PriceListCustomerFallbackRepository $customerFallbackRepository */
        $customerFallbackRepository = $this->getEntityManager()
            ->getRepository(PriceListCustomerFallback::class);
        $qb = $customerFallbackRepository->getBaseQbForFallback($websiteId);

        $qb
            ->leftJoin(
                PriceListCustomerGroupFallback::class,
                'customerGroupFallback',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('customer.group', 'customerGroupFallback.customerGroup'),
                    $qb->expr()->eq('customerGroupFallback.website', ':website')
                )
            )
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('customerGroupFallback.id'),
                    $qb->expr()->eq('customerGroupFallback.fallback', ':fallbackGroup')
                )
            )
            ->setParameter('website', $websiteId, Types::INTEGER)
            ->setParameter('fallbackGroup', PriceListCustomerGroupFallback::WEBSITE, Types::INTEGER);

        $iterator = new BufferedIdentityQueryResultIterator($qb);
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);

        return $iterator;
    }
}
