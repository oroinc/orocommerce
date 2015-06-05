<?php

namespace OroB2B\Bundle\PricingBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductPriceImportStrategy extends ConfigurableAddOrReplaceStrategy
{
    /**
     * @param ProductPrice $entity
     * @return ProductPrice
     */
    protected function beforeProcessEntity($entity)
    {
        $entity->loadPrice();

        $this->loadProduct($entity);

        return parent::beforeProcessEntity($entity);
    }

    /**
     * @param ProductPrice $entity
     */
    protected function loadProduct(ProductPrice $entity)
    {
        if ($entity->getProduct()) {
            /** @var Product $product */
            $product = $this->findExistingEntity($entity->getProduct());
            $entity->setProduct($product);
        }
    }
}
