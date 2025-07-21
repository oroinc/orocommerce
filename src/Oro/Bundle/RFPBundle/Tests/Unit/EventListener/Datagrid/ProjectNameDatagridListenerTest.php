<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\EventListener\Datagrid\ProjectNameDatagridListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProjectNameDatagridListenerTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private ProjectNameDatagridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new ProjectNameDatagridListener($this->configManager);
    }

    public function testOnBuildBeforeWhenRfqProjectNameDisabled(): void
    {
        $datagridConfig = [
            'source' => [
                'query' => [
                    'select' => [
                        'request.id',
                        'request.firstName'
                    ],
                    'from' => [
                        ['table' => Request::class, 'alias' => 'request']
                    ]
                ]
            ],
            'columns' => [
                'id' => ['label' => 'id.label'],
                'firstName' => ['label' => 'first_name.label']
            ],
            'filters' => [
                'columns' => [
                    'id' => ['data_name' => 'request.id'],
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
                        'request.firstName'
                    ],
                    'from' => [
                        ['table' => Request::class, 'alias' => 'request']
                    ]
                ]
            ],
            'columns' => [
                'id' => ['label' => 'id.label'],
                'firstName' => ['label' => 'first_name.label']
            ],
            'filters' => [
                'columns' => [
                    'id' => ['data_name' => 'request.id'],
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
                    'projectName' => ['label' => 'oro.rfp.request.project_name.label'],
                    'firstName' => ['label' => 'first_name.label']
                ],
                'filters' => [
                    'columns' => [
                        'id' => ['data_name' => 'request.id'],
                        'projectName' => ['type' => 'string', 'data_name' => 'request.projectName'],
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
