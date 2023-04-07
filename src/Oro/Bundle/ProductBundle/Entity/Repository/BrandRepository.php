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
    public function getBrandByOrganizationQueryBuilder(OrganizationInterface $organization): QueryBuilder
    {
        return $this
            ->createQueryBuilder('b')
            ->leftJoin('b.organization', 'organization')
            ->where('organization = :organization')
            ->setParameter('organization', $organization);
    }
}
