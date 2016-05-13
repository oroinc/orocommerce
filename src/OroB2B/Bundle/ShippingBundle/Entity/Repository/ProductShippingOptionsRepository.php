<?php

namespace OroB2B\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;

class ProductShippingOptionsRepository extends EntityRepository
{
    /**
     * @param Product $product
     * @return ProductShippingOptions[]|array
     */
    public function getShippingOptionsByProduct(Product $product)
    {
        $qb = $this->createQueryBuilder('pso');

        return $qb->andWhere('pso.product = :product')
            ->setParameter('product', $product)
            ->addOrderBy($qb->expr()->asc('pso.productUnit'))
            ->getQuery()
            ->getResult();
    }
}
