<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Doctrine repository for {@see ProductKitItem} entity.
 */
class ProductKitItemRepository extends ServiceEntityRepository
{
    /**
     * Returns an array of SKUs of the product kits that reference specified $productUnitPrecision.
     *
     * @param ProductUnitPrecision $productUnitPrecision
     * @param int $limit
     *
     * @return string[]
     */
    public function findProductKitsSkuByUnitPrecision(
        ProductUnitPrecision $productUnitPrecision,
        int $limit = 10
    ): array {
        $qb = $this->createQueryBuilder('pki');

        return $qb
            ->select('pk.sku')
            ->innerJoin('pki.productKit', 'pk')
            ->innerJoin('pki.kitItemProducts', 'pkip')
            ->where($qb->expr()->eq('pkip.productUnitPrecision', ':product_unit_precision_id'))
            ->setParameter('product_unit_precision_id', $productUnitPrecision->getId(), Types::INTEGER)
            ->groupBy('pk.id')
            ->orderBy('pk.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN);
    }

    /**
     * Returns an array of SKUs of the product kits that reference specified $product.
     *
     * @param Product $product
     * @param int $limit
     *
     * @return string[]
     */
    public function findProductKitsSkuByProduct(Product $product, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('pki');

        return $qb
            ->select('pk.sku')
            ->innerJoin('pki.productKit', 'pk')
            ->innerJoin('pki.kitItemProducts', 'pip')
            ->innerJoin(
                'pip.product',
                'p',
                Join::WITH,
                $qb->expr()->eq('p.id', ':product_id')
            )
            ->setParameter('product_id', $product->getId(), Types::INTEGER)
            ->groupBy('pk.id')
            ->setMaxResults($limit)
            ->orderBy('pk.id', 'DESC')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN);
    }

    /**
     * @param int $productKitId
     *
     * @return int Number of kit items related to the product kit with id $productKitId.
     */
    public function getKitItemsCount(int $productKitId): int
    {
        $qb = $this->createQueryBuilder('pki');

        return $qb
            ->select($qb->expr()->count('pki.id'))
            ->where($qb->expr()->eq('pki.productKit', ':product_kit_id'))
            ->setParameter('product_kit_id', $productKitId, Types::INTEGER)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }
}
