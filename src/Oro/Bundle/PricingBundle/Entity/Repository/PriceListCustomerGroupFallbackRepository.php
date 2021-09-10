<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Repository for PriceListCustomerGroupFallback entity
 */
class PriceListCustomerGroupFallbackRepository extends EntityRepository
{
    /**
     * @param int $websiteId
     * @return \Iterator
     */
    public function getCustomerIdentityByWebsite($websiteId)
    {
        /** @var PriceListCustomerFallbackRepository $customerFallbackRepository */
        $customerFallbackRepository = $this->getEntityManager()
            ->getRepository('OroPricingBundle:PriceListCustomerFallback');
        $qb = $customerFallbackRepository->getBaseQbForFallback($websiteId);

        $qb->leftJoin(
            'OroPricingBundle:PriceListCustomerGroupFallback',
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
        ->setParameter('website', $websiteId)
        ->setParameter('fallbackGroup', PriceListCustomerGroupFallback::WEBSITE);

        $iterator = new BufferedIdentityQueryResultIterator($qb);
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);

        return $iterator;
    }

    public function hasFallbackOnNextLevel(Website $website, CustomerGroup $customerGroup): bool
    {
        $qb = $this->createQueryBuilder('f');
        $qb->select('f.id')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('f.website', ':website'),
                    $qb->expr()->eq('f.customerGroup', ':customerGroup'),
                    $qb->expr()->eq('f.fallback', ':fallback')
                )
            )->setParameters([
                'website' => $website,
                'customerGroup' => $customerGroup,
                'fallback' => PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY
            ])
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult() === null;
    }
}
