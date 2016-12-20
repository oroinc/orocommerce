<?php

namespace Oro\Bundle\OrderBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrderRepository extends EntityRepository
{
    /**
     * @param array             $removingCurrencies
     * @param Organization|null $organization
     *
     * @return bool
     */
    public function hasRecordsWithRemovingCurrencies(
        array $removingCurrencies,
        Organization $organization = null
    ) {
        $qb = $this->createQueryBuilder('orders');
        $qb
            ->select('count(orders.id)')
            ->where($qb->expr()->in('orders.currency', $removingCurrencies));
        if ($organization instanceof Organization) {
            $qb->andWhere('orders.organization = :organization');
            $qb->setParameter(':organization', $organization);
        }

        return (bool) $qb->getQuery()->getSingleScalarResult();
    }
}
