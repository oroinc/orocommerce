<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

/**
 * Product price attributes add or replace import strategy.
 * Set quantity to 1, ensure that price is loaded correctly.
 */
class PriceAttributeProductPriceImportStrategy extends ConfigurableAddOrReplaceStrategy
{
    /**
     * @param PriceAttributeProductPrice $entity
     *
     * @return PriceAttributeProductPrice
     */
    protected function beforeProcessEntity($entity)
    {
        $this->setPrice($entity);

        $entity->setQuantity(1);

        return parent::beforeProcessEntity($entity);
    }

    /**
     * @param PriceAttributeProductPrice $entity
     *
     * @return PriceAttributeProductPrice
     */
    protected function afterProcessEntity($entity)
    {
        $this->setPrice($entity);

        return parent::afterProcessEntity($entity);
    }

    protected function setPrice(PriceAttributeProductPrice $entity)
    {
        $entity->loadPrice();
    }
}
