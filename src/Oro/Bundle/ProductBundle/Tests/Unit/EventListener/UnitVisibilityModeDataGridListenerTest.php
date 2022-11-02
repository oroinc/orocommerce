<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ProductBundle\EventListener\UnitVisibilityModeDataGridListener;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;

class UnitVisibilityModeDataGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SingleUnitModeService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $singleModeProvider;

    /**
     * @var BuildBefore|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventMock;

    /**
     * @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dataGridMock;

    /**
     * @var array
     */
    private $basicOnBuildBeforeTestData = [
        'unitColumnName' => 'unit',
        'quantityColumnName' => 'quantity',
        'quantityTemplate' => 'TemplatePath',
        'quantityTemplateContext' => ['someContextVar' => 1],
        'initialQuantityColumnParams' => ['someParam1' => 'var1', 'someParam2' => 'var2'],
        'initialUnitColumnParams' => ['someUnitParam1' => 'var1', 'someUnitParam2' => 'var2'],
    ];

    protected function setUp(): void
    {
        $this->singleModeProvider = $this
            ->getMockBuilder(SingleUnitModeService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataGridMock = $this
            ->getMockBuilder(DatagridInterface::class)
            ->getMock();

        $this->eventMock = $this
            ->getMockBuilder(BuildBefore::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSingleModeEnabled()
    {
        $expectedQuantityResult = array_merge(
            [
                'context' => array_merge(
                    [
                        'quantityColumnName' => $this->basicOnBuildBeforeTestData['quantityColumnName'],
                        'unitColumnName' => $this->basicOnBuildBeforeTestData['unitColumnName'],
                    ],
                    $this->basicOnBuildBeforeTestData['quantityTemplateContext']
                ),
                'type' => UnitVisibilityModeDataGridListener::TEMPLATE_TYPE,
                'template' => $this->basicOnBuildBeforeTestData['quantityTemplate'],
            ],
            $this->basicOnBuildBeforeTestData['initialQuantityColumnParams']
        );
        $expectedUnitResult = null;

        $quantityColumnPath = sprintf(
            DatagridConfiguration::COLUMN_PATH,
            $this->basicOnBuildBeforeTestData['quantityColumnName']
        );

        $unitColumnPath = sprintf(
            DatagridConfiguration::COLUMN_PATH,
            $this->basicOnBuildBeforeTestData['unitColumnName']
        );

        $config = DatagridConfiguration::create([]);
        $config->offsetSetByPath($quantityColumnPath, $this->basicOnBuildBeforeTestData['initialQuantityColumnParams']);
        $config->offsetSetByPath($unitColumnPath, $this->basicOnBuildBeforeTestData['initialUnitColumnParams']);

        $this->dataGridMock
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->eventMock
            ->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->dataGridMock);

        $this->singleModeProvider
            ->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(true);

        $listener = new UnitVisibilityModeDataGridListener(
            $this->basicOnBuildBeforeTestData['unitColumnName'],
            $this->basicOnBuildBeforeTestData['quantityColumnName'],
            $this->basicOnBuildBeforeTestData['quantityTemplate'],
            $this->basicOnBuildBeforeTestData['quantityTemplateContext'],
            $this->singleModeProvider
        );

        $listener->onBuildBefore($this->eventMock);

        $this->assertEquals($expectedQuantityResult, $config->offsetGetByPath($quantityColumnPath));
        $this->assertEquals($expectedUnitResult, $config->offsetGetByPath($unitColumnPath));
    }

    public function testSingleModeDisabled()
    {
        $expectedQuantityResult = $this->basicOnBuildBeforeTestData['initialQuantityColumnParams'];
        $expectedUnitResult = $this->basicOnBuildBeforeTestData['initialUnitColumnParams'];

        $quantityColumnPath = sprintf(
            DatagridConfiguration::COLUMN_PATH,
            $this->basicOnBuildBeforeTestData['quantityColumnName']
        );

        $unitColumnPath = sprintf(
            DatagridConfiguration::COLUMN_PATH,
            $this->basicOnBuildBeforeTestData['unitColumnName']
        );

        $config = DatagridConfiguration::create([]);
        $config->offsetSetByPath($quantityColumnPath, $this->basicOnBuildBeforeTestData['initialQuantityColumnParams']);
        $config->offsetSetByPath($unitColumnPath, $this->basicOnBuildBeforeTestData['initialUnitColumnParams']);

        $this->dataGridMock
            ->expects($this->never())
            ->method('getConfig');

        $this->eventMock
            ->expects($this->never())
            ->method('getDatagrid');

        $this->singleModeProvider
            ->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(false);

        $this->singleModeProvider
            ->expects($this->never())
            ->method('isSingleUnitModeCodeVisible');

        $listener = new UnitVisibilityModeDataGridListener(
            $this->basicOnBuildBeforeTestData['unitColumnName'],
            $this->basicOnBuildBeforeTestData['quantityColumnName'],
            $this->basicOnBuildBeforeTestData['quantityTemplate'],
            $this->basicOnBuildBeforeTestData['quantityTemplateContext'],
            $this->singleModeProvider
        );

        $listener->onBuildBefore($this->eventMock);

        $this->assertEquals($expectedQuantityResult, $config->offsetGetByPath($quantityColumnPath));
        $this->assertEquals($expectedUnitResult, $config->offsetGetByPath($unitColumnPath));
    }
}
