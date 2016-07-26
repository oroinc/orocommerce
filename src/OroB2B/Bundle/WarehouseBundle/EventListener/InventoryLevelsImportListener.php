<?php

namespace OroB2B\Bundle\WarehouseBundle\EventListener;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Event\StepExecutionEvent;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;

use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy\WarehouseInventoryLevelStrategy;

class InventoryLevelsImportListener
{
    const INVENTORY_IMPORT_PROCESSOR_ALIAS = 'orob2b_warehouse.warehouse_inventory_level';

    /** @var  WarehouseInventoryLevelStrategy $inventoryLevelStrategy */
    protected $inventoryLevelStrategy;

    public function __construct(WarehouseInventoryLevelStrategy $inventoryLevelStrategy)
    {
        $this->inventoryLevelStrategy = $inventoryLevelStrategy;
    }

    public function onBatchStepCompleted(StepExecutionEvent $event)
    {
        /** @var StepExecution $stepExecution */
        $stepExecution = $event->getStepExecution();

        /** @var JobExecution $jobExecution */
        $jobExecution = $stepExecution->getJobExecution();

        /** @var ExecutionContext $context */
        $context = $jobExecution->getExecutionContext();

        $processorAlias = $context->get('processorAlias');
        $entityName = $context->get('entityName');
        if (!($processorAlias == self::INVENTORY_IMPORT_PROCESSOR_ALIAS
            && $entityName == WarehouseInventoryLevel::class)
        ) {
            return;
        }

        $this->inventoryLevelStrategy->clearCache();
    }
}
