<?php

namespace Oro\Bundle\RFPBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class RequestRepository extends EntityRepository
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
        $qb = $this->createQueryBuilder('r');
        $qb
            ->select('count(r.id)')
            ->leftJoin('r.requestProducts', 'requestProducts')
            ->leftJoin('requestProducts.requestProductItems', 'requestProductItems')
            ->where($qb->expr()->in('requestProductItems.currency', $removingCurrencies));
        if ($organization instanceof Organization) {
            $qb->andWhere('r.organization = :organization');
            $qb->setParameter(':organization', $organization);
        }

        return (bool) $qb->getQuery()->getSingleScalarResult();
    }
}
