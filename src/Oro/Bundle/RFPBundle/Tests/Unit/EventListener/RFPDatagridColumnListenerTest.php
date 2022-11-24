<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\RFPBundle\EventListener\RFPDatagridColumnListener;
use Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener;

class RFPDatagridColumnListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider buildBeforeDataProvider
     */
    public function testBuildBefore(array $configuration, string $datagridName, array $expected)
    {
        $listener = new RFPDatagridColumnListener();
        $event = $this->createBuildBeforeEvent($configuration, $datagridName);
        $listener->onBuildBefore($event);

        $this->assertEquals($expected, $event->getConfig()->toArray());
    }

    public function buildBeforeDataProvider(): array
    {
        return [
            'workflow step column not defined' => [
                'configuration' => [
                    'columns' => [
                        'test' => ['label' => 'Test'],
                    ]
                ],
                'datagridName' => 'test_name',
                'expected' => [
                    'columns' => [
                        'test' => ['label' => 'Test'],
                    ]
                ]
            ],
            'workflow step column defined' => [
                'configuration' => [
                    'columns' => [
                        WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => ['label' => 'Test'],
                    ]
                ],
                'datagridName' => 'rfp-requests-grid',
                'expected' => [
                    'columns' => [
                        WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => ['label' => 'Test', 'renderable' => false],
                    ]
                ]
            ]
        ];
    }

    private function createBuildBeforeEvent(array $configuration, string $datagridName): BuildBefore
    {
        $datagridConfiguration = DatagridConfiguration::create($configuration);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->any())
            ->method('getName')
            ->willReturn($datagridName);

        $event = $this->createMock(BuildBefore::class);
        $event->expects($this->any())
            ->method('getConfig')
            ->willReturn($datagridConfiguration);
        $event->expects($this->any())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        return $event;
    }
}
