<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;

/**
 * Returns NULL when tries import existing entity (decreases `replaced` counter).
 */
class RelatedProductStrategy extends ConfigurableAddOrReplaceStrategy
{
    #[\Override]
    protected function findEntityByIdentityValues($entityName, array $identityValues)
    {
        if (isset($identityValues['sku']) && is_a($entityName, Product::class, true)) {
            $identityValues['skuUppercase'] = mb_strtoupper($identityValues['sku']);
            unset($identityValues['sku']);
        }

        return parent::findEntityByIdentityValues($entityName, $identityValues);
    }

    #[\Override]
    protected function importEntityFields($entity, $existingEntity, $isFullData, $entityIsRelation, $itemData)
    {
        if ($existingEntity instanceof Product && $entity instanceof Product) {
            return $existingEntity;
        }

        return parent::importEntityFields($entity, $existingEntity, $isFullData, $entityIsRelation, $itemData);
    }

    #[\Override]
    protected function combineIdentityValues($entity, $entityClass, array $searchContext)
    {
        if ($entityClass === RelatedProduct::class) {
            return null;
        }

        return parent::combineIdentityValues(
            $entity,
            $entityClass,
            $searchContext
        );
    }

    #[\Override]
    protected function afterProcessEntity($entity)
    {
        if ($entity instanceof RelatedProduct && $entity->getId()) {
            return null;
        }

        return parent::afterProcessEntity($entity);
    }

    #[\Override]
    protected function updateContextCounters($entity): void
    {
    }
}
