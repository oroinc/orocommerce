<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\AbstractImportStrategy;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class WarehouseInventoryLevelStrategy extends AbstractImportStrategy
{
    /** @var  WarehouseInventoryLevelStrategyHelperInterface $inventoryLevelStrategyHelper */
    protected $inventoryLevelStrategyHelper;

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $this->assertEnvironment($entity);

        if (!$entity = $this->beforeProcessEntity($entity)) {
            return null;
        }

        if (!$entity = $this->processEntity($entity, $this->context->getValue('itemData'))) {
            return null;
        }

        if (!$entity = $this->afterProcessEntity($entity)) {
            return null;
        }

        return $entity;
    }

    /**
     * @param WarehouseInventoryLevel $entity
     * @param null|array $itemData
     * @return mixed
     */
    protected function processEntity(
        $entity,
        $itemData = null
    ) {
        $entity = $this->inventoryLevelStrategyHelper->process($entity, $itemData);

        foreach ($this->inventoryLevelStrategyHelper->getErrors(true) as $error => $prefix) {
            $this->strategyHelper->addValidationErrors([$error], $this->context, $prefix);
        }

        if ($entity) {
            $id = $this->databaseHelper->getIdentifier($entity);
            if (empty($id)) {
                $this->context->incrementAddCount();
            } else {
                $this->context->incrementUpdateCount();
            }
        } else {
            $this->context->incrementErrorEntriesCount();
        }

        return $entity;
    }

    /**
     * @param WarehouseInventoryLevelStrategyHelperInterface $inventoryLevelStrategyHelper
     */
    public function setInventoryLevelStrategyHelper(
        WarehouseInventoryLevelStrategyHelperInterface $inventoryLevelStrategyHelper
    ) {
        $this->inventoryLevelStrategyHelper = $inventoryLevelStrategyHelper;
    }

    /**
     * Clear caches craeted on strategy helpers
     */
    public function clearCache()
    {
        $this->inventoryLevelStrategyHelper->clearCache(true);
    }
}
