<?php

namespace Oro\Bundle\InventoryBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Doctrine repository for InventoryLevel entity.
 */
class InventoryLevelRepository extends EntityRepository
{
    public function getLevelByProductAndProductUnit(Product $product, ProductUnit $productUnit): ?InventoryLevel
    {
        return $this->getProductAndProductUnitQueryBuilder($product, $productUnit)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Product[] $products
     *
     * @return array [['product_id' => product id, 'code' => product unit, 'quantity' => quantity], ...]
     */
    public function getQuantityForProductCollection(array $products): array
    {
        return $this->createQueryBuilder('il')
            ->select('IDENTITY(il.product) as product_id, IDENTITY(pup.unit) as code, SUM(il.quantity) as quantity')
            ->leftJoin('il.productUnitPrecision', 'pup')
            ->where('il.product IN (:products)')
            ->setParameter('products', $products)
            ->groupBy('il.product, pup.unit')
            ->getQuery()
            ->getArrayResult();
    }

    public function deleteInventoryLevelByProductAndProductUnitPrecision(
        Product $product,
        ProductUnitPrecision $productUnitPrecision
    ): void {
        $this->createQueryBuilder('il')
            ->delete(InventoryLevel::class, 'il')
            ->where('il.product = :product')
            ->andWhere('il.productUnitPrecision = :productUnitPrecision')
            ->setParameter('product', $product)
            ->setParameter('productUnitPrecision', $productUnitPrecision)
            ->getQuery()
            ->execute();
    }

    protected function getProductAndProductUnitQueryBuilder(Product $product, ProductUnit $productUnit): QueryBuilder
    {
        return $this->createQueryBuilder('il')
            ->leftJoin('il.productUnitPrecision', 'pup')
            ->where('il.product = :product')
            ->andWhere('pup.unit = :productUnit')
            ->setParameter('product', $product)
            ->setParameter('productUnit', $productUnit);
    }
}
