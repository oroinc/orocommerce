<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\OrderBundle\Datagrid\OrderStatusDatagridListener;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;

class OrderStatusDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderConfigurationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var EnumOptionsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $enumOptionsProvider;

    /** @var OrderStatusDatagridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(OrderConfigurationProviderInterface::class);
        $this->enumOptionsProvider = $this->createMock(EnumOptionsProvider::class);

        $this->listener = new OrderStatusDatagridListener($this->configurationProvider, $this->enumOptionsProvider);
    }

    public function testOnBuildBeforeWhenExternalStatusManagementDisabled(): void
    {
        $config = [
            'source'  => [
                'query' => [
                    'select' => [
                        'order1.identifier',
                        'internalStatus.name as internalStatusName',
                        'internalStatus.id as internalStatusId'
                    ],
                    'from'   => [
                        ['table' => Order::class, 'alias' => 'order1']
                    ],
                    'join'   => [
                        'left' => [
                            ['join' => 'order1.internal_status', 'alias' => 'internalStatus']
                        ]
                    ]
                ]
            ],
            'columns' => [
                'identifier'         => ['label' => 'identifier'],
                'internalStatusName' => ['label' => 'oro.order.internal_status.label']
            ],
            'filters' => [
                'columns' => [
                    'identifier'         => ['data_name' => 'order1.identifier'],
                    'internalStatusName' => [
                        'type'      => 'enum',
                        'data_name' => 'internalStatusId',
                        'enum_code' => 'order_internal_status'
                    ]
                ]
            ],
            'sorters' => [
                'columns' => [
                    'identifier'         => ['data_name' => 'order1.identifier'],
                    'internalStatusName' => ['data_name' => 'internalStatusName']
                ]
            ]
        ];
        $gridConfig = DatagridConfiguration::create($config);

        $this->configurationProvider->expects(self::once())
            ->method('isExternalStatusManagementEnabled')
            ->willReturn(false);

        $this->listener->onBuildBefore(new BuildBefore($this->createMock(DatagridInterface::class), $gridConfig));

        self::assertSame($config, $gridConfig->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnBuildBeforeWhenExternalStatusManagementEnabled(): void
    {
        $gridConfig = DatagridConfiguration::create([
            'source'  => [
                'query' => [
                    'select' => [
                        'order1.identifier',
                        'internalStatus.name as internalStatusName',
                        'internalStatus.id as internalStatusId'
                    ],
                    'from'   => [
                        ['table' => Order::class, 'alias' => 'order1']
                    ],
                    'join'   => [
                        'left' => [
                            ['join' => 'order1.internal_status', 'alias' => 'internalStatus']
                        ]
                    ]
                ]
            ],
            'columns' => [
                'identifier'         => ['label' => 'identifier'],
                'internalStatusName' => ['label' => 'oro.order.internal_status.label']
            ],
            'filters' => [
                'columns' => [
                    'identifier'         => ['data_name' => 'order1.identifier'],
                    'internalStatusName' => [
                        'type'      => 'enum',
                        'data_name' => 'internalStatusId',
                        'enum_code' => 'order_internal_status'
                    ]
                ]
            ],
            'sorters' => [
                'columns' => [
                    'identifier'         => ['data_name' => 'order1.identifier'],
                    'internalStatusName' => ['data_name' => 'internalStatusName']
                ]
            ]
        ]);

        $this->configurationProvider->expects(self::once())
            ->method('isExternalStatusManagementEnabled')
            ->willReturn(true);

        $this->listener->onBuildBefore(new BuildBefore($this->createMock(DatagridInterface::class), $gridConfig));

        self::assertSame(
            [
                'source'  => [
                    'query' => [
                        'select' => [
                            'order1.identifier',
                            'internalStatus.name as internalStatusName',
                            'internalStatus.id as internalStatusId'
                        ],
                        'from'   => [
                            ['table' => Order::class, 'alias' => 'order1']
                        ],
                        'join'   => [
                            'left' => [
                                ['join' => 'order1.internal_status', 'alias' => 'internalStatus']
                            ]
                        ]
                    ]
                ],
                'columns' => [
                    'identifier'         => ['label' => 'identifier'],
                    'internalStatusName' => ['label' => 'oro.order.internal_status.label'],
                    'status'         => [
                        'label' => 'oro.order.status.label',
                        'frontend_type' => 'select',
                        'data_name' => 'status',
                        'choices' => [],
                        'translatable_options' => false,

                    ],
                ],
                'filters' => [
                    'columns' => [
                        'identifier'         => ['data_name' => 'order1.identifier'],
                        'internalStatusName' => [
                            'type'      => 'enum',
                            'data_name' => 'internalStatusId',
                            'enum_code' => 'order_internal_status'
                        ],
                        'status'         => [
                            'type'      => 'enum',
                            'data_name' => 'status',
                            'enum_code' => 'order_status'
                        ]
                    ]
                ],
                'sorters' => [
                    'columns' => [
                        'identifier'         => ['data_name' => 'order1.identifier'],
                        'internalStatusName' => ['data_name' => 'internalStatusName'],
                        'status'         => ['data_name' => 'status']
                    ]
                ]
            ],
            $gridConfig->toArray()
        );
    }
}
