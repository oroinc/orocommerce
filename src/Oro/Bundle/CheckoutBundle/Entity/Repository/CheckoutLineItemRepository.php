<?php

namespace Oro\Bundle\CheckoutBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Handles logic for fetching checkout items.
 */
class CheckoutLineItemRepository extends EntityRepository
{
    public function canBeGrouped(int $checkoutId): bool
    {
        $qb = $this->createQueryBuilder('li');
        $qb->resetDQLPart('select')
            ->select($qb->expr()->count('li.id'))
            ->where(
                $qb->expr()->in('li.checkout', ':checkout'),
                $qb->expr()->isNotNull('li.parentProduct')
            )
            ->setParameter('checkout', $checkoutId)
            ->groupBy('li.parentProduct')
            ->having($qb->expr()->gt($qb->expr()->count('li.id'), 1))
            ->setMaxResults(1);

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }
}
