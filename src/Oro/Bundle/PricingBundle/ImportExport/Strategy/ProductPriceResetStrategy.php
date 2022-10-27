<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Strategy;

/**
 * Product price reset strategy
 * It expects the existing prices to be removed from the price list before import
 */
class ProductPriceResetStrategy extends ProductPriceImportStrategy
{
    /**
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        // no need to search product prices in storage
        if (is_a($entity, $this->entityName)) {
            return null;
        }

        return parent::findExistingEntity($entity, $searchContext);
    }
}
