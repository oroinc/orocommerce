<?php

namespace OroB2B\Bundle\TaxBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductTaxCodeRepository extends EntityRepository
{
    /**
     * @param Product $product
     *
     * @return ProductTaxCode|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByProduct(Product $product)
    {
        return $this->createQueryBuilder('productTaxCode')
            ->where(':product MEMBER OF productTaxCode.products')
            ->setParameter('product', $product)
            ->getQuery()->getOneOrNullResult();
    }
}
