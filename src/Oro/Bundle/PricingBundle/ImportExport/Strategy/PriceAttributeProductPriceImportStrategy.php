<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

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

    /**
     * @param PriceAttributeProductPrice $entity
     */
    protected function setPrice(PriceAttributeProductPrice $entity)
    {
        $entity->loadPrice();
    }
}
