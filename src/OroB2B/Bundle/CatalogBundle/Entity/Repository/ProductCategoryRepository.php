<?php

namespace OroB2B\Bundle\CatalogBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\CatalogBundle\Entity\ProductCategory;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductCategoryRepository extends EntityRepository
{
    /**
     * @param Product $product
     *
     * @return null|ProductCategory
     */
    public function findOneByProduct(Product $product)
    {
        return $this->findOneBy(['product' => $product]);
    }
}
