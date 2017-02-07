<?php

namespace Oro\Bundle\InventoryBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class InventoryLevelRepository extends EntityRepository
{
    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @return InventoryLevel
     */
    public function getLevelByProductAndProductUnit(Product $product, ProductUnit $productUnit)
    {
        return $this->getQBForProductAndProductUnit($product, $productUnit)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getQBForProductAndProductUnit(Product $product, ProductUnit $productUnit)
    {
        return $this->createQueryBuilder('il')
            ->leftJoin('il.productUnitPrecision', 'pup')
            ->where('il.product = :product')
            ->andWhere('pup.unit = :productUnit')
            ->setParameter('product', $product)
            ->setParameter('productUnit', $productUnit);
    }
}
