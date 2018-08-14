<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractAssignerRepositoryInterface;

/**
 * Doctrine repository for RelatedProduct entity. Introduces methods to get count of related items, fetch collection of
 * related products and collection of ids of related products.
 */
class RelatedProductRepository extends EntityRepository implements AbstractAssignerRepositoryInterface
{
    /**
     * @param Product|int $productFrom
     * @param Product|int $productTo
     * @return bool
     */
    public function exists($productFrom, $productTo)
    {
        return null !== $this->findOneBy(['product' => $productFrom, 'relatedItem' => $productTo]);
    }

    /**
     * @param int $id
     * @return int
     */
    public function countRelationsForProduct($id)
    {
        return (int) $this->createQueryBuilder('related_products')
            ->select('COUNT(related_products.id)')
            ->where('related_products.product = :id')
            ->setParameter(':id', $id)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int $id
     * @param bool $bidirectional
     * @param int|null $limit
     * @return int[]
     */
    public function findRelatedIds($id, $bidirectional, $limit = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('DISTINCT IDENTITY(rp.relatedItem) as id')
            ->from(RelatedProduct::class, 'rp')
            ->where($qb->expr()->eq('rp.product', ':id'))
            ->setParameter('id', $id)
            ->orderBy('rp.relatedItem');
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        $productIds = $qb->getQuery()->getArrayResult();
        $productIds = array_column($productIds, 'id');
        if ($bidirectional) {
            if ($limit === null || count($productIds) < $limit) {
                $qb = $this->getEntityManager()->createQueryBuilder()
                    ->select('DISTINCT IDENTITY(rp.product) as id')
                    ->from(RelatedProduct::class, 'rp')
                    ->where($qb->expr()->eq('rp.relatedItem', ':id'))
                    ->andWhere($qb->expr()->notIn('rp.product', ':alreadySelectedIds'))
                    ->setParameter('id', $id)
                    ->setParameter('alreadySelectedIds', $productIds)
                    ->orderBy('rp.product');
                if ($limit) {
                    $qb->setMaxResults($limit - count($productIds));
                }
                $biProductIds = $qb->getQuery()->getArrayResult();
                $biProductIds = array_column($biProductIds, 'id');
                $productIds = array_merge($productIds, $biProductIds);
            }
        }

        return $productIds;
    }

    /**
     * @param int $id
     * @param bool $bidirectional
     * @param int|null $limit
     * @return Product[]
     */
    public function findRelated($id, $bidirectional, $limit = null)
    {
        $productIds = $this->findRelatedIds($id, $bidirectional, $limit);

        $products = [];
        if ($productIds) {
            $products = $this->getEntityManager()
                ->getRepository(Product::class)
                ->findBy(['id' => $productIds], ['id' => 'ASC'], $limit);
        }

        return $products;
    }
}
