<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductUnitPrecisionRepository extends EntityRepository
{
    /**
     * @param array $ids
     * @return mixed
     */
    public function deleteProductUnitPrecisionsById(array $ids)
    {
        $queryBuilder = $this->createQueryBuilder('pup');
        $queryBuilder->delete(ProductUnitPrecision::class, 'pup')
            ->where('pup.id in (:ids)')
            ->setParameter('ids', $ids);

        return $queryBuilder->getQuery()->execute();
    }
}
