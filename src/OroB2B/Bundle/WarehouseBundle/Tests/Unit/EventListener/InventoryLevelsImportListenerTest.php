<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\EventListener;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Event\StepExecutionEvent;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;

use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\EventListener\InventoryLevelsImportListener;
use OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy\WarehouseInventoryLevelStrategy;

class InventoryLevelsImportListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WarehouseInventoryLevelStrategy|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $warehouseInventoryLevelStrategy;

    /**
     * @var InventoryLevelsImportListener
     */
    protected $inventoryLevelsImportListener;

    protected function setUp()
    {
        $this->warehouseInventoryLevelStrategy = $this->getMockBuilder(WarehouseInventoryLevelStrategy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryLevelsImportListener = new InventoryLevelsImportListener(
            $this->warehouseInventoryLevelStrategy
        );
    }

    public function testShouldClearCache()
    {
        $event = $this->getEvent(
            InventoryLevelsImportListener::INVENTORY_IMPORT_PROCESSOR_ALIAS,
            WarehouseInventoryLevel::class
        );

        $this->warehouseInventoryLevelStrategy->expects($this->exactly(1))
            ->method('clearCache');

        $this->inventoryLevelsImportListener->onBatchStepCompleted($event);
    }

    public function testShouldNotClearCache()
    {
        $event = $this->getEvent(
            'some other alias',
            'some class'
        );

        $this->warehouseInventoryLevelStrategy->expects($this->exactly(0))
            ->method('clearCache');

        $this->inventoryLevelsImportListener->onBatchStepCompleted($event);
    }

    /**
     * @param string $processorAlias
     * @param string $entityName
     *
     * @return StepExecution
     */
    protected function getEvent($processorAlias, $entityName)
    {
        $executionContext = new ExecutionContext();
        $executionContext->put('processorAlias', $processorAlias);
        $executionContext->put('entityName', $entityName);

        $jobExecution = new JobExecution();
        $jobExecution->setExecutionContext($executionContext);

        $stepExecution = new StepExecution('import', $jobExecution);

        return new StepExecutionEvent($stepExecution);
    }
}
