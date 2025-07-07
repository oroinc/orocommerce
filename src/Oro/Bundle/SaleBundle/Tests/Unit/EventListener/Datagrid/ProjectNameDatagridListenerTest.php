<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\EventListener\Datagrid\ProjectNameDatagridListener;
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

    public function testOnBuildBeforeWhenQuoteProjectNameDisabled(): void
    {
        $datagridConfig = [
            'source' => [
                'query' => [
                    'select' => [
                        'quote.qid',
                        'quote.poNumber'
                    ],
                    'from' => [
                        ['table' => Quote::class, 'alias' => 'quote']
                    ]
                ]
            ],
            'columns' => [
                'qid' => ['label' => 'qid.label'],
                'poNumber' => ['label' => 'po_number.label']
            ],
            'filters' => [
                'columns' => [
                    'qid' => ['data_name' => 'quote.qid'],
                    'poNumber' => ['data_name' => 'quote.poNumber']
                ]
            ],
            'sorters' => [
                'columns' => [
                    'qid' => ['data_name' => 'quote.qid'],
                    'poNumber' => ['data_name' => 'quote.poNumber']
                ]
            ]
        ];
        $config = DatagridConfiguration::create($datagridConfig);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_sale.enable_quote_project_name')
            ->willReturn(false);

        $event = new BuildBefore($this->createMock(DatagridInterface::class), $config);
        $this->listener->onBuildBefore($event);

        self::assertSame($datagridConfig, $config->toArray());
    }

    public function testOnBuildBeforeWhenQuoteProjectNameEnabled(): void
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'query' => [
                    'select' => [
                        'quote.qid',
                        'quote.poNumber'
                    ],
                    'from' => [
                        ['table' => Quote::class, 'alias' => 'quote']
                    ]
                ]
            ],
            'columns' => [
                'qid' => ['label' => 'qid.label'],
                'poNumber' => ['label' => 'po_number.label']
            ],
            'filters' => [
                'columns' => [
                    'qid' => ['data_name' => 'quote.qid'],
                    'poNumber' => ['data_name' => 'quote.poNumber']
                ]
            ],
            'sorters' => [
                'columns' => [
                    'qid' => ['data_name' => 'quote.qid'],
                    'poNumber' => ['data_name' => 'quote.poNumber']
                ]
            ]
        ]);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_sale.enable_quote_project_name')
            ->willReturn(true);

        $event = new BuildBefore($this->createMock(DatagridInterface::class), $config);
        $this->listener->onBuildBefore($event);

        self::assertSame(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            'quote.qid',
                            'quote.poNumber',
                            'quote.projectName'
                        ],
                        'from' => [
                            ['table' => Quote::class, 'alias' => 'quote']
                        ]
                    ]
                ],
                'columns' => [
                    'qid' => ['label' => 'qid.label'],
                    'projectName' => ['label' => 'oro.sale.quote.project_name.label'],
                    'poNumber' => ['label' => 'po_number.label']
                ],
                'filters' => [
                    'columns' => [
                        'qid' => ['data_name' => 'quote.qid'],
                        'projectName' => ['type' => 'string', 'data_name' => 'quote.projectName'],
                        'poNumber' => ['data_name' => 'quote.poNumber']
                    ]
                ],
                'sorters' => [
                    'columns' => [
                        'qid' => ['data_name' => 'quote.qid'],
                        'poNumber' => ['data_name' => 'quote.poNumber'],
                        'projectName' => ['data_name' => 'quote.projectName']
                    ]
                ]
            ],
            $config->toArray()
        );
    }
}
