<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

class WarehouseInventoryLevelStrategy extends ConfigurableAddOrReplaceStrategy
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

        if (!$entity = $this->processEntity($entity, true, true, $this->context->getValue('itemData'))) {
            return null;
        }

        if (!$entity = $this->afterProcessEntity($entity)) {
            return null;
        }

        return $entity;
    }
        /**
     * @inheritdoc
     */
    protected function processEntity(
        $entity,
        $isFullData = false,
        $isPersistNew = false,
        $itemData = null,
        array $searchContext = array(),
        $entityIsRelation = false
    ) {
        $entity = $this->inventoryLevelStrategyHelper->process($entity, $itemData);

        foreach ($this->inventoryLevelStrategyHelper->getErrors(true) as $error => $prefix) {
            $this->strategyHelper->addValidationErrors([$error], $this->context, $prefix);
        }

        if ($entity) {
            $id = $this->databaseHelper->getIdentifier($entity);
            if (!empty($id)) {
                $this->context->incrementUpdateCount();
            } else {
                $this->context->incrementAddCount();
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
}
