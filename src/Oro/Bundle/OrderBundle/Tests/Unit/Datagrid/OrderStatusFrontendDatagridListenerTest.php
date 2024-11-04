<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\OrderBundle\Datagrid\OrderStatusFrontendDatagridListener;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;

class OrderStatusFrontendDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderConfigurationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var EnumOptionsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $enumOptionsProvider;

    /** @var OrderStatusFrontendDatagridListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(OrderConfigurationProviderInterface::class);
        $this->enumOptionsProvider = $this->createMock(EnumOptionsProvider::class);

        $this->listener = new OrderStatusFrontendDatagridListener(
            $this->configurationProvider,
            $this->enumOptionsProvider
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnBuildBeforeWhenExternalStatusManagementDisabled(): void
    {
        $config = [
            'source'  => [
                'query' => [
                    'select' => [
                        'order1.identifier'
                    ],
                    'from'   => [
                        ['table' => Order::class, 'alias' => 'order1']
                    ],
                    'join'   => [
                        'left' => [
                            ['join' => PaymentStatus::class, 'alias' => 'payment_status']
                        ]
                    ]
                ]
            ],
            'columns' => [
                'identifier'    => ['label' => 'identifier'],
                'total'         => ['label' => 'total'],
                'paymentStatus' => ['label' => 'payment_status']
            ],
            'filters' => [
                'columns' => [
                    'identifier'    => ['data_name' => 'order1.identifier'],
                    'total'         => ['data_name' => 'order1.total'],
                    'paymentStatus' => ['data_name' => 'payment_status']
                ]
            ],
            'sorters' => [
                'columns' => [
                    'identifier'    => ['data_name' => 'order1.identifier'],
                    'total'         => ['data_name' => 'order1.total'],
                    'paymentStatus' => ['data_name' => 'payment_status']
                ]
            ]
        ];
        $gridConfig = DatagridConfiguration::create($config);

        $this->configurationProvider->expects(self::once())
            ->method('isExternalStatusManagementEnabled')
            ->willReturn(false);
        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumChoicesByCode')
            ->with('order_internal_status')
            ->willReturn(['Open' => 'open', 'Closed' => 'closed']);

        $this->listener->onBuildBefore(new BuildBefore($this->createMock(DatagridInterface::class), $gridConfig));

        self::assertSame(
            [
                'source'  => [
                    'query' => [
                        'select' => [
                            'order1.identifier',
                            'status.name as statusName',
                            'status.id as statusId'
                        ],
                        'from'   => [
                            ['table' => Order::class, 'alias' => 'order1']
                        ],
                        'join'   => [
                            'left' => [
                                ['join' => PaymentStatus::class, 'alias' => 'payment_status'],
                                [
                                    'join' => EnumOption::class,
                                    'alias' => 'status',
                                    'conditionType' => 'WITH',
                                    'condition' => "JSON_EXTRACT(order1.serialized_data, 'internal_status') = status"
                                ]
                            ]
                        ]
                    ],
                    'hints' => ['HINT_TRANSLATABLE']
                ],
                'columns' => [
                    'identifier'    => ['label' => 'identifier'],
                    'statusName'    => ['label' => 'oro.frontend.order.order_status.label'],
                    'total'         => ['label' => 'total'],
                    'paymentStatus' => ['label' => 'payment_status']
                ],
                'filters' => [
                    'columns' => [
                        'identifier'    => ['data_name' => 'order1.identifier'],
                        'statusName'    => [
                            'type'      => 'choice',
                            'data_name' => 'statusId',
                            'options'   => [
                                'field_options' => [
                                    'choices'              => ['Open' => 'open', 'Closed' => 'closed'],
                                    'translatable_options' => false,
                                    'multiple'             => true
                                ]
                            ]
                        ],
                        'total'         => ['data_name' => 'order1.total'],
                        'paymentStatus' => ['data_name' => 'payment_status']
                    ]
                ],
                'sorters' => [
                    'columns' => [
                        'identifier'    => ['data_name' => 'order1.identifier'],
                        'total'         => ['data_name' => 'order1.total'],
                        'paymentStatus' => ['data_name' => 'payment_status'],
                        'statusName'    => ['data_name' => 'statusName']
                    ]
                ]
            ],
            $gridConfig->toArray()
        );
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
                        'order1.identifier'
                    ],
                    'from'   => [
                        ['table' => Order::class, 'alias' => 'order1']
                    ],
                    'join'   => [
                        'left' => [
                            ['join' => PaymentStatus::class, 'alias' => 'payment_status']
                        ]
                    ]
                ]
            ],
            'columns' => [
                'identifier'    => ['label' => 'identifier'],
                'total'         => ['label' => 'total'],
                'paymentStatus' => ['label' => 'payment_status']
            ],
            'filters' => [
                'columns' => [
                    'identifier'    => ['data_name' => 'order1.identifier'],
                    'total'         => ['data_name' => 'order1.total'],
                    'paymentStatus' => ['data_name' => 'payment_status']
                ]
            ],
            'sorters' => [
                'columns' => [
                    'identifier'    => ['data_name' => 'order1.identifier'],
                    'total'         => ['data_name' => 'order1.total'],
                    'paymentStatus' => ['data_name' => 'payment_status']
                ]
            ]
        ]);

        $this->configurationProvider->expects(self::once())
            ->method('isExternalStatusManagementEnabled')
            ->willReturn(true);
        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumChoicesByCode')
            ->with('order_status')
            ->willReturn(['Open' => 'open', 'Closed' => 'closed']);

        $this->listener->onBuildBefore(new BuildBefore($this->createMock(DatagridInterface::class), $gridConfig));

        self::assertSame(
            [
                'source'  => [
                    'query' => [
                        'select' => [
                            'order1.identifier',
                            'status.name as statusName',
                            'status.id as statusId'
                        ],
                        'from'   => [
                            ['table' => Order::class, 'alias' => 'order1']
                        ],
                        'join'   => [
                            'left' => [
                                ['join' => PaymentStatus::class, 'alias' => 'payment_status'],
                                [
                                    'join' => EnumOption::class,
                                    'alias' => 'status',
                                    'conditionType' => 'WITH',
                                    'condition' => "JSON_EXTRACT(order1.serialized_data, 'status') = status"
                                ]
                            ]
                        ]
                    ],
                    'hints' => ['HINT_TRANSLATABLE']
                ],
                'columns' => [
                    'identifier'    => ['label' => 'identifier'],
                    'statusName'    => ['label' => 'oro.frontend.order.order_status.label'],
                    'total'         => ['label' => 'total'],
                    'paymentStatus' => ['label' => 'payment_status']
                ],
                'filters' => [
                    'columns' => [
                        'identifier'    => ['data_name' => 'order1.identifier'],
                        'statusName'    => [
                            'type'      => 'choice',
                            'data_name' => 'statusId',
                            'options'   => [
                                'field_options' => [
                                    'choices'              => ['Open' => 'open', 'Closed' => 'closed'],
                                    'translatable_options' => false,
                                    'multiple'             => true
                                ]
                            ]
                        ],
                        'total'         => ['data_name' => 'order1.total'],
                        'paymentStatus' => ['data_name' => 'payment_status']
                    ]
                ],
                'sorters' => [
                    'columns' => [
                        'identifier'    => ['data_name' => 'order1.identifier'],
                        'total'         => ['data_name' => 'order1.total'],
                        'paymentStatus' => ['data_name' => 'payment_status'],
                        'statusName'    => ['data_name' => 'statusName']
                    ]
                ]
            ],
            $gridConfig->toArray()
        );
    }
}
