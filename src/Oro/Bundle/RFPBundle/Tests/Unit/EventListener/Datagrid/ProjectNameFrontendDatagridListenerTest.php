<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\EventListener\Datagrid\ProjectNameFrontendDatagridListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProjectNameFrontendDatagridListenerTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private ProjectNameFrontendDatagridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new ProjectNameFrontendDatagridListener($this->configManager);
    }

    public function testOnBuildBeforeWhenRfqProjectNameDisabled(): void
    {
        $datagridConfig = [
            'source' => [
                'query' => [
                    'select' => [
                        'request.id',
                        'request.customer_status',
                        'request.firstName'
                    ],
                    'from' => [
                        ['table' => Request::class, 'alias' => 'request']
                    ]
                ]
            ],
            'columns' => [
                'id' => ['label' => 'id.label'],
                'customer_status' => ['label' => 'customer_status.label'],
                'firstName' => ['label' => 'first_name.label']
            ],
            'filters' => [
                'columns' => [
                    'customer_status' => ['data_name' => 'request.customer_status'],
                    'firstName' => ['data_name' => 'request.firstName']
                ]
            ],
            'sorters' => [
                'columns' => [
                    'id' => ['data_name' => 'request.id'],
                    'firstName' => ['data_name' => 'request.firstName']
                ]
            ]
        ];
        $config = DatagridConfiguration::create($datagridConfig);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_rfp.enable_rfq_project_name')
            ->willReturn(false);

        $event = new BuildBefore($this->createMock(DatagridInterface::class), $config);
        $this->listener->onBuildBefore($event);

        self::assertSame($datagridConfig, $config->toArray());
    }

    public function testOnBuildBeforeWhenRfqProjectNameEnabled(): void
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'query' => [
                    'select' => [
                        'request.id',
                        'request.customer_status',
                        'request.firstName'
                    ],
                    'from' => [
                        ['table' => Request::class, 'alias' => 'request']
                    ]
                ]
            ],
            'columns' => [
                'id' => ['label' => 'id.label'],
                'customer_status' => ['label' => 'customer_status.label'],
                'firstName' => ['label' => 'first_name.label']
            ],
            'filters' => [
                'columns' => [
                    'customer_status' => ['data_name' => 'request.customer_status'],
                    'firstName' => ['data_name' => 'request.firstName']
                ]
            ],
            'sorters' => [
                'columns' => [
                    'id' => ['data_name' => 'request.id'],
                    'firstName' => ['data_name' => 'request.firstName']
                ]
            ]
        ]);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_rfp.enable_rfq_project_name')
            ->willReturn(true);

        $event = new BuildBefore($this->createMock(DatagridInterface::class), $config);
        $this->listener->onBuildBefore($event);

        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            'request.id',
                            'request.customer_status',
                            'request.firstName',
                            'request.projectName'
                        ],
                        'from' => [
                            ['table' => Request::class, 'alias' => 'request']
                        ]
                    ]
                ],
                'columns' => [
                    'id' => ['label' => 'id.label'],
                    'projectName' => ['label' => 'oro.frontend.rfp.request.project_name.label'],
                    'customer_status' => ['label' => 'customer_status.label'],
                    'firstName' => ['label' => 'first_name.label']
                ],
                'filters' => [
                    'columns' => [
                        'projectName' => ['type' => 'string', 'data_name' => 'request.projectName'],
                        'customer_status' => ['data_name' => 'request.customer_status'],
                        'firstName' => ['data_name' => 'request.firstName']
                    ]
                ],
                'sorters' => [
                    'columns' => [
                        'id' => ['data_name' => 'request.id'],
                        'firstName' => ['data_name' => 'request.firstName'],
                        'projectName' => ['data_name' => 'request.projectName']
                    ]
                ]
            ],
            $config->toArray()
        );
    }
}
