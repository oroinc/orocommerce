<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\UpsellProduct;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractAssignerRepositoryInterface;

class UpsellProductRepository extends EntityRepository implements AbstractAssignerRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function exists($productFrom, $productTo)
    {
        return null !== $this->findOneBy(['product' => $productFrom, 'relatedItem' => $productTo]);
    }

    /**
     * {@inheritdoc}
     */
    public function countRelationsForProduct($id)
    {
        return (int) $this->createQueryBuilder('upsell_products')
            ->select('COUNT(upsell_products.id)')
            ->where('upsell_products.product = :id')
            ->setParameter(':id', $id)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int $id
     * @param int|null $limit
     * @return Product[]
     */
    public function findUpsell($id, $limit = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->from('OroProductBundle:Product', 'p')
            ->select('p')
            ->leftJoin(UpsellProduct::class, 'up_l', Join::WITH, 'up_l.relatedItem = p.id')
            ->where('up_l.product = :id')
            ->setParameter(':id', $id)
            ->orderBy('p.id');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->execute();
    }
}
