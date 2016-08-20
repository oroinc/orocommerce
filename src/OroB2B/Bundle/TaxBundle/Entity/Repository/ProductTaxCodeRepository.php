<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

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
