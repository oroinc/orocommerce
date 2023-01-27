<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Strategy;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;

/**
 * Product price reset strategy
 * It expects the existing prices to be removed from the price list before import
 */
class ProductPriceResetStrategy extends ProductPriceImportStrategy
{
    private const VALIDATION_GROUP = 'ProductPriceResetAndAddImport';

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

    protected function validateAndUpdateContext($entity): ?ProductPrice
    {
        $validationErrors = $this->strategyHelper->validateEntity($entity, null, self::VALIDATION_GROUP);
        if ($validationErrors) {
            $this->processValidationErrors($entity, $validationErrors);

            return null;
        }

        $this->updateContextCounters($entity);

        return parent::validateEntityUniqueness($entity);
    }
}
