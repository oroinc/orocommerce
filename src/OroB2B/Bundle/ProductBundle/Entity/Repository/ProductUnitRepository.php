<?php

namespace OroB2B\Bundle\ProductBundle\Entity\Repository;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductUnitRepository extends EntityRepository
{
    /**
     * @param Product $product
     * @return ProductUnit[]
     */
    public function getProductUnits(Product $product)
    {
        return $this->getProductUnitsQueryBuilder($product)->getQuery()->getResult();
    }

    /**
     * @param array $products
     * @return array
     */
    public function getProductsUnits(array $products = [])
    {
        $qb = $this->createQueryBuilder('unit');
        $qb
            ->select('IDENTITY(productUnitPrecision.product) AS productId, unit.code AS code')
            ->join(
                'OroB2BProductBundle:ProductUnitPrecision',
                'productUnitPrecision',
                Join::WITH,
                $qb->expr()->eq('productUnitPrecision.unit', 'unit')
            )
            ->addOrderBy('unit.code')
            ->where($qb->expr()->in('productUnitPrecision.product', ':products'))
            ->setParameter('products', $products);
        $productsUnits = $qb->getQuery()->getArrayResult();

        $result = [];
        foreach ($productsUnits as $unit) {
            $result[$unit['productId']][] = $unit['code'];
        }

        return $result;
    }

    /**
     * @return ProductUnit[]
     */
    public function getAllUnits()
    {
        return $this->findBy([], ['code' => 'ASC']);
    }

    /**
     * @param Product $product
     *
     * @return QueryBuilder
     */
    public function getProductUnitsQueryBuilder(Product $product)
    {
        $qb = $this->createQueryBuilder('unit');
        $qb
            ->select('unit')
            ->join(
                'OroB2BProductBundle:ProductUnitPrecision',
                'productUnitPrecision',
                Join::WITH,
                $qb->expr()->eq('productUnitPrecision.unit', 'unit')
            )
            ->addOrderBy('unit.code')
            ->where($qb->expr()->eq('productUnitPrecision.product', ':product'))
            ->setParameter('product', $product);

        return $qb;
    }

    /**
     * @param int $productId
     * @return QueryBuilder
     *
     * @throws \InvalidArgumentException if id is not valid
     * @throws EntityNotFoundException if entity not found by id
     */
    public function getProductUnitsQueryBuilderById($productId)
    {
        if (!is_numeric($productId)) {
            throw new \InvalidArgumentException();
        }

        $product = $this->_em->getReference('OroB2BProductBundle:Product', (int)$productId);
        if (!$product) {
            throw new EntityNotFoundException();
        }

        /** @var Product $product */
        return $this->getProductUnitsQueryBuilder($product);
    }
}
