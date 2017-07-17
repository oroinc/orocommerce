<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;

class RelatedProductRepository extends EntityRepository
{
    /**
     * @param Product|int $productFrom
     * @param Product|int $productTo
     * @return bool
     */
    public function exists($productFrom, $productTo)
    {
        return null !== $this->findOneBy(['product' => $productFrom, 'relatedProduct' => $productTo]);
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
            ->from('OroProductBundle:Product', 'p')
            ->select('p')
            ->leftJoin(RelatedProduct::class, 'rp_r', Join::WITH, 'rp_r.relatedProduct = p.id')
            ->where('rp_r.product = :id')
            ->setParameter(':id', $id)
            ->orderBy('p.id')
            ->groupBy('p.id');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($bidirectional) {
            $qb->leftJoin(RelatedProduct::class, 'rp_l', Join::WITH, 'rp_l.product = p.id')
                ->orWhere('rp_l.relatedProduct = :id');
        }

        return $qb->getQuery()->execute();
    }
}
