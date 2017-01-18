<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;

class PriceListCustomerFallbackRepository extends EntityRepository
{
    /**
     * @param array $customerGroups
     * @param int $websiteId
     * @return BufferedQueryResultIterator|array
     */
    public function getCustomerIdentityByGroup(array $customerGroups, $websiteId)
    {
        if (empty($customerGroups)) {
            return [];
        }
        $qb = $this->getBaseQbForFallback($websiteId);

        $qb->andWhere($qb->expr()->in('customer.group', ':groups'))
            ->setParameter('groups', $customerGroups);

        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);

        return $iterator;
    }

    /**
     * @param int $websiteId
     * @return QueryBuilder
     */
    public function getBaseQbForFallback($websiteId)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('DISTINCT customer.id')
            ->from('OroCustomerBundle:Customer', 'customer');
        $qb->leftJoin(
            'OroPricingBundle:PriceListCustomerFallback',
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
        ->setParameter('website', $websiteId)
        ->setParameter('fallback', PriceListCustomerFallback::ACCOUNT_GROUP);

        return $qb;
    }
}
