<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractAssignerRepositoryInterface;

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
     * @return Product[]
     */
    public function findRelated($id, $bidirectional, $limit = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT IDENTITY(rp.relatedItem) as id')
            ->from(RelatedProduct::class, 'rp')
            ->where('rp.product = :id')
            ->setParameter(':id', $id)
            ->orderBy('rp.relatedItem');
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        $productIds = $qb->getQuery()->getArrayResult();
        $productIds = array_column($productIds, 'id');
        $products = [];
        if ($bidirectional) {
            if ($limit === null || count($productIds) < $limit) {
                $qb = $this->getEntityManager()->createQueryBuilder()
                    ->select('DISTINCT IDENTITY(rp.product) as id')
                    ->from(RelatedProduct::class, 'rp')
                    ->where('rp.relatedItem = :id')
                    ->setParameter(':id', $id)
                    ->orderBy('rp.product');
                if ($limit) {
                    $qb->setMaxResults($limit);
                }
                $biProductIds = $qb->getQuery()->getArrayResult();
                $biProductIds = array_column($biProductIds, 'id');
                $productIds = array_merge($productIds, $biProductIds);
            }
        }

        if ($productIds) {
            $products = $this->getEntityManager()
                ->getRepository(Product::class)
                ->findBy(['id' => $productIds], ['id' => 'ASC'], $limit);
        }

        return $products;
    }
}
