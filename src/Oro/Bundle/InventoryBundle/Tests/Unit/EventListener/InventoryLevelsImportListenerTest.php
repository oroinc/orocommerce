<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Event\StepExecutionEvent;
use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\EventListener\InventoryLevelsImportListener;
use Oro\Bundle\InventoryBundle\ImportExport\Strategy\InventoryLevelStrategy;

class InventoryLevelsImportListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var InventoryLevelStrategy|\PHPUnit\Framework\MockObject\MockObject */
    private $inventoryLevelStrategy;

    /** @var InventoryLevelsImportListener */
    private $inventoryLevelsImportListener;

    protected function setUp(): void
    {
        $this->inventoryLevelStrategy = $this->createMock(InventoryLevelStrategy::class);

        $this->inventoryLevelsImportListener = new InventoryLevelsImportListener(
            $this->inventoryLevelStrategy
        );
    }

    public function testShouldClearCache()
    {
        $event = $this->getEvent(
            InventoryLevelsImportListener::INVENTORY_IMPORT_PROCESSOR_ALIAS,
            InventoryLevel::class
        );

        $this->inventoryLevelStrategy->expects($this->once())
            ->method('clearCache');

        $this->inventoryLevelsImportListener->onBatchStepCompleted($event);
    }

    public function testShouldNotClearCache()
    {
        $event = $this->getEvent(
            'some other alias',
            'some class'
        );

        $this->inventoryLevelStrategy->expects($this->never())
            ->method('clearCache');

        $this->inventoryLevelsImportListener->onBatchStepCompleted($event);
    }

    private function getEvent(string $processorAlias, string $entityName): StepExecutionEvent
    {
        $executionContext = new ExecutionContext();
        $executionContext->put('processorAlias', $processorAlias);
        $executionContext->put('entityName', $entityName);

        $jobExecution = new JobExecution();
        $jobExecution->setExecutionContext($executionContext);

        return new StepExecutionEvent(new StepExecution('import', $jobExecution));
    }
}
