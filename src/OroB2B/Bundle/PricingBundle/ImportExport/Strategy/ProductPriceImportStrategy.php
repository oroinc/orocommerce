<?php

namespace OroB2B\Bundle\PricingBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class ProductPriceImportStrategy extends ConfigurableAddOrReplaceStrategy
{
    /**
     * @param ProductPrice $entity
     * @return ProductPrice
     */
    protected function beforeProcessEntity($entity)
    {
        $entity->loadPrice();

        return parent::beforeProcessEntity($entity);
    }
}
