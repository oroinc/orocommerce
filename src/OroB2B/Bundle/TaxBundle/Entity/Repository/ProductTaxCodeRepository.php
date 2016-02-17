<?php

namespace OroB2B\Bundle\TaxBundle\Entity\Repository;

use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\TaxBundle\Model\TaxCodeInterface;

class ProductTaxCodeRepository extends AbstractTaxCodeRepository
{
    /**
     * @param Product $product
     *
     * @return ProductTaxCode|null
     */
    public function findOneByProduct(Product $product)
    {
        if (!$product->getId()) {
            return null;
        }

        return $this->findOneByEntity(TaxCodeInterface::TYPE_PRODUCT, $product);
    }
}
