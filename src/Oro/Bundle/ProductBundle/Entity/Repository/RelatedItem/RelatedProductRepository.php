<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

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
            ->from('OroProductBundle:Product', 'p')
            ->select('p')
            ->setParameter(':id', $id)
            ->orderBy('p.id')
            ->groupBy('p.id');
        $subQb = $this->getEntityManager()->createQueryBuilder()
            ->select('rp_r.id')
            ->from(RelatedProduct::class, 'rp_r')
            ->where('rp_r.relatedItem = p.id and rp_r.product = :id');
        $qb->where($qb->expr()->exists($subQb->getDQL()));
        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($bidirectional) {
            $subQb = $this->getEntityManager()->createQueryBuilder()
                ->select('rp_l.id')
                ->from(RelatedProduct::class, 'rp_l')
                ->where('rp_l.product = p.id and rp_l.relatedItem = :id');
            $qb->orWhere($qb->expr()->exists($subQb->getDQL()));
        }

        return $qb->getQuery()->execute();
    }
}
