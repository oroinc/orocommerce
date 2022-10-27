<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Event\StepExecutionEvent;
use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\ImportExport\Strategy\InventoryLevelStrategy;

/**
 * Clears cache on the InventoryLevelStrategy when a batch job step is completed.
 */
class InventoryLevelsImportListener
{
    const INVENTORY_IMPORT_PROCESSOR_ALIAS = 'oro_inventory.inventory_level';

    /** @var  InventoryLevelStrategy $inventoryLevelStrategy */
    protected $inventoryLevelStrategy;

    /**
     * InventoryLevelsImportListener constructor.
     */
    public function __construct(InventoryLevelStrategy $inventoryLevelStrategy)
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
            && $entityName == InventoryLevel::class)
        ) {
            return;
        }

        $this->inventoryLevelStrategy->clearCache();
    }
}
