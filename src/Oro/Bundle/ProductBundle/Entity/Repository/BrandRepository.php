<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\RedirectBundle\Entity\Slug;

/**
 * Doctrine repository for the Brand entity
 */
class BrandRepository extends EntityRepository
{
    public function findOneBySlug(Slug $slug): ?Brand
    {
        $qb = $this->createQueryBuilder('b');
        $qb
            ->where($qb->expr()->isMemberOf(':slug', 'b.slugs'))
            ->setParameter('slug', $slug);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
