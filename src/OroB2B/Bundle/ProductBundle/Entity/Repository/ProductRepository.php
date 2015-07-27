<?php

namespace OroB2B\Bundle\ProductBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class ProductRepository extends EntityRepository
{
    /**
     * @param array $productIds
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getProductsQueryBuilder(array $productIds = [])
    {
        $productsQueryBuilder = $this
            ->createQueryBuilder('p')
            ->select('p');

        if (count($productIds) > 0) {
            $productsQueryBuilder
                ->where('p IN (:product_ids)')
                ->setParameter('product_ids', $productIds);
        }

        return $productsQueryBuilder;
    }
}
