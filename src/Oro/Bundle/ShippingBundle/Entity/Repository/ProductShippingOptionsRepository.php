<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;

class ProductShippingOptionsRepository extends EntityRepository
{
    /**
     * @param Product[]     $products
     * @param ProductUnit[] $productUnits
     *
     * @return ProductShippingOptions[]
     */
    public function findByProductsAndProductUnits(array $products, array $productUnits): array
    {
        return $this->findBy([
            'product' => $products,
            'productUnit' => $productUnits,
        ]);
    }
}
