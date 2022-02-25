<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Doctrine repository for ProductUnit entity
 */
class ProductUnitRepository extends ServiceEntityRepository
{
    /**
     * @param Product $product
     *
     * @return ProductUnit[]
     */
    public function getProductUnits(Product $product): array
    {
        return $this->getProductUnitsQueryBuilder($product)->getQuery()->getResult();
    }

    /**
     * @param Product[] $products
     *
     * @return array [product id => [unit code => unit precision, ...], ...]
     */
    public function getProductsUnits(array $products): array
    {
        if (count($products) === 0) {
            return [];
        }

        $productsUnits = $this->getProductsUnitsQueryBuilder($products)
            ->select(
                'IDENTITY(productUnitPrecision.product) AS productId, unit.code AS code,
                 COALESCE(IDENTITY(product.primaryUnitPrecision), 0) as primary,
                 productUnitPrecision.precision'
            )
            ->getQuery()->getArrayResult();

        $result = [];
        foreach ($productsUnits as $unit) {
            if ($unit['primary'] && isset($result[$unit['productId']])) {
                $result[$unit['productId']] = array_merge(
                    [$unit['code'] => $unit['precision']],
                    $result[$unit['productId']]
                );
            } else {
                $result[$unit['productId']][$unit['code']] = $unit['precision'];
            }
        }

        return $result;
    }

    /**
     * @param array|int[]|Product[] $products
     * @param ProductUnit $unit
     * @param bool $onlySellable
     * @return array|int[]
     */
    public function getProductIdsSupportUnit(
        array $products,
        ProductUnit $unit,
        bool $onlySellable = true
    ): array {
        if (count($products) === 0) {
            return [];
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->from('OroProductBundle:ProductUnitPrecision', 'productUnitPrecision')
            ->select('IDENTITY(productUnitPrecision.product) as productId')
            ->where($qb->expr()->in('productUnitPrecision.product', ':products'))
            ->andWhere($qb->expr()->eq('productUnitPrecision.unit', ':unit'));

        if ($onlySellable) {
            $qb->andWhere('productUnitPrecision.sell = true');
        }

        $qb->setParameter('products', $products);
        $qb->setParameter('unit', $unit);

        $rawResult = $qb->getQuery()->getArrayResult();

        return \array_column($rawResult, 'productId');
    }

    /**
     * @param Product[] $products
     *
     * @return array [product id => unit code, ...]
     */
    public function getPrimaryProductsUnits(array $products): array
    {
        if (count($products) === 0) {
            return [];
        }

        $productsUnits = $this->getProductsUnitsQueryBuilder($products)
            ->select(
                'IDENTITY(productUnitPrecision.product) AS productId, unit.code AS code,
                 COALESCE(IDENTITY(product.primaryUnitPrecision), 0) as primary'
            )
            ->getQuery()->getArrayResult();

        $result = [];
        foreach ($productsUnits as $unit) {
            if ($unit['primary']) {
                $result[$unit['productId']] = $unit['code'];
            }
        }

        return $result;
    }

    /**
     * @param int[] $productIds
     *
     * @return array [product id => [unit code, ...], ...]
     */
    public function getProductsUnitsByProductIds(array $productIds): array
    {
        if (!$productIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('unit')
            ->select('IDENTITY(unitPrecision.product) AS productId, unit.code AS code')
            ->innerJoin(ProductUnitPrecision::class, 'unitPrecision', Join::WITH, 'unitPrecision.unit = unit')
            ->andWhere('unitPrecision.sell = true')
            ->andWhere('unitPrecision.product IN (:products)')
            ->setParameter('products', $productIds)
            ->addOrderBy('unitPrecision.unit')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['productId']][] = $row['code'];
        }

        return $result;
    }

    private function getProductsUnitsQueryBuilder(array $products): QueryBuilder
    {
        $qb = $this->createQueryBuilder('unit');
        $qb->innerJoin(
            ProductUnitPrecision::class,
            'productUnitPrecision',
            Join::WITH,
            'productUnitPrecision.unit = unit'
        )
            ->leftJoin(
                Product::class,
                'product',
                Join::WITH,
                'product.primaryUnitPrecision = productUnitPrecision'
            )
            ->addOrderBy('productUnitPrecision.unit')
            ->andWhere('productUnitPrecision.sell = true')
            ->andWhere('productUnitPrecision.product IN (:products)')
            ->setParameter('products', $products);

        return $qb;
    }

    public function getProductsUnitsByCodes(array $products, array $codes): array
    {
        if (count($products) === 0 || count($codes) === 0) {
            return [];
        }

        $qb = $this->getProductsUnitsQueryBuilder($products);
        $qb->andWhere('unit IN (:units)')
            ->setParameter('units', $codes);

        return array_reduce($qb->getQuery()->execute(), function ($result, ProductUnit $unit) {
            $result[$unit->getCode()] = $unit;
            return $result;
        }, []);
    }

    /**
     * @return ProductUnit[]
     */
    public function getAllUnits(): array
    {
        return $this->findBy([], ['code' => 'ASC']);
    }

    public function getProductUnitsQueryBuilder(Product $product): QueryBuilder
    {
        $qb = $this->createQueryBuilder('unit');
        $qb
            ->select('unit')
            ->innerJoin(
                ProductUnitPrecision::class,
                'productUnitPrecision',
                Join::WITH,
                'productUnitPrecision.unit = unit'
            )
            ->addOrderBy('unit.code')
            ->andWhere('productUnitPrecision.product = :product')
            ->setParameter('product', $product);

        return $qb;
    }

    /**
     * @return string[]
     */
    public function getAllUnitCodes(): array
    {
        $results = $this->createQueryBuilder('unit')
            ->select('unit.code')
            ->orderBy('unit.code')
            ->getQuery()
            ->getScalarResult();

        return array_column($results, 'code');
    }
}
