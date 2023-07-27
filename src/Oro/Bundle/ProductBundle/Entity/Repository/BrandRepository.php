<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

/**
 * Doctrine repository for the Brand entity
 */
class BrandRepository extends EntityRepository
{
    /**
     * Loads brands with an initialized collection of names.
     */
    public function getBrandQueryBuilder(): QueryBuilder
    {
        return $this
            ->createQueryBuilder('b')
            ->select('b,n')
            ->leftJoin('b.names', 'n');
    }

    public function getBrandByOrganizationQueryBuilder(OrganizationInterface $organization): QueryBuilder
    {
        return $this
            ->getBrandQueryBuilder()
            ->leftJoin('b.organization', 'organization')
            ->where('organization = :organization')
            ->setParameter('organization', $organization);
    }
}
