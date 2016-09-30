<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Event\StepExecutionEvent;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\ImportExport\Strategy\InventoryLevelStrategy;

class InventoryLevelsImportListener
{
    const INVENTORY_IMPORT_PROCESSOR_ALIAS = 'oro_inventory.inventory_level';

    /** @var  InventoryLevelStrategy $inventoryLevelStrategy */
    protected $inventoryLevelStrategy;

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
