<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;

/**
 * Contains business specific methods for retrieving product image entities.
 */
class ProductImageRepository extends EntityRepository
{
    public function getAllProductImagesIterator(): \Iterator
    {
        $qb = $this->createQueryBuilder('pi')->select('pi.id');

        $iterator = new BufferedIdentityQueryResultIterator($qb);

        return $iterator;
    }
}
