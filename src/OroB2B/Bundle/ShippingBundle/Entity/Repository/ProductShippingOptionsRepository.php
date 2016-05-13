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
        return $this->findBy(['product' => $product], ['productUnit' => 'ASC']);
    }
}
