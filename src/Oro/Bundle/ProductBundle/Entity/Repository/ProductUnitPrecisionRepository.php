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

    /**
     * @param $productId
     * @return array
     */
    public function getProductUnitPrecisionsByProductId($productId)
    {
        $queryBuilder = $this->createQueryBuilder('pup');
        $queryBuilder->select('pup', 'pu')
            ->innerJoin('pup.product', 'p')
            ->innerJoin('pup.unit', 'pu')
            ->where('p.id = :productId')
            ->setParameter('productId', $productId);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param $productId
     * @return null|ProductUnitPrecision
     */
    public function getPrimaryUnitPrecisionByProductId($productId)
    {
        $queryBuilder = $this->createQueryBuilder('pup');
        $queryBuilder->select('pup')
            ->innerJoin('pup.product', 'p', 'WITH', 'p.primaryUnitPrecision = pup.id')
            ->where('p.id = :productId')
            ->setParameter('productId', $productId);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
