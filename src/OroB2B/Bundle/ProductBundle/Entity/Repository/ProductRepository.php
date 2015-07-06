<?php

namespace OroB2B\Bundle\ProductBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class ProductRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function getAllSku()
    {
        return $this
            ->createQueryBuilder('product')
            ->select('product.sku')
            ->getQuery()
            ->getResult();
    }
}
