<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener\DataGrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\EventListener\DataGrid\PaymentStatusFilterDatagridListener;
use Oro\Bundle\PaymentBundle\Filter\PaymentStatusFilter;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PaymentStatusFilterDatagridListenerTest extends TestCase
{
    private PaymentStatusFilterDatagridListener $listener;

    protected function setUp(): void
    {
        $this->listener = new PaymentStatusFilterDatagridListener();
    }

    public function testOnBuildBeforeWithReportGridAndPaymentStatusFilter(): void
    {
        $rootEntity = Order::class;

        $config = DatagridConfiguration::create([
            'source' => [
                'type' => 'orm',
                'query' => [
                    'from' => [
                        ['table' => $rootEntity, 'alias' => 'o']
                    ]
                ]
            ],
            'filters' => [
                'columns' => [
                    'payment_status' => [
                        'type' => PaymentStatusFilter::NAME,
                        'options' => []
                    ],
                    'other_filter' => [
                        'type' => 'string',
                        'options' => []
                    ]
                ]
            ]
        ]);

        $event = $this->createBuildBeforeEvent(Report::GRID_PREFIX . 'test', $config);

        $this->listener->onBuildBefore($event);

        $configArray = $config->toArray();
        self::assertEquals(
            $rootEntity,
            $configArray['filters']['columns']['payment_status']['options']['target_entity']
        );

        // Verify other filters are not modified
        self::assertArrayNotHasKey(
            'target_entity',
            $configArray['filters']['columns']['other_filter']['options']
        );
    }

    public function testOnBuildBeforeWithMultiplePaymentStatusFilters(): void
    {
        $rootEntity = Order::class;

        $config = DatagridConfiguration::create([
            'source' => [
                'type' => 'orm',
                'query' => [
                    'from' => [
                        ['table' => $rootEntity, 'alias' => 'o']
                    ]
                ]
            ],
            'filters' => [
                'columns' => [
                    'payment_status1' => [
                        'type' => PaymentStatusFilter::NAME,
                        'options' => ['existing_option' => 'value']
                    ],
                    'payment_status2' => [
                        'type' => PaymentStatusFilter::NAME,
                        'options' => []
                    ]
                ]
            ]
        ]);

        $event = $this->createBuildBeforeEvent(Report::GRID_PREFIX . 'multi_test', $config);

        $this->listener->onBuildBefore($event);

        $configArray = $config->toArray();

        // Both payment-status filters should have target_entity set
        self::assertEquals(
            $rootEntity,
            $configArray['filters']['columns']['payment_status1']['options']['target_entity']
        );
        self::assertEquals(
            $rootEntity,
            $configArray['filters']['columns']['payment_status2']['options']['target_entity']
        );

        // Existing options should be preserved
        self::assertEquals(
            'value',
            $configArray['filters']['columns']['payment_status1']['options']['existing_option']
        );
    }

    public function testOnBuildBeforeWithReportGridButNoFilters(): void
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'type' => 'orm',
                'query' => [
                    'from' => [
                        ['table' => PaymentStatus::class, 'alias' => 'ps']
                    ]
                ]
            ]
        ]);
        $originalConfig = $config->toArray();

        $event = $this->createBuildBeforeEvent(Report::GRID_PREFIX . 'no_filters', $config);

        $this->listener->onBuildBefore($event);

        // Configuration should remain unchanged
        self::assertEquals($originalConfig, $config->toArray());
    }

    public function testOnBuildBeforeWithReportGridButEmptyFilterColumns(): void
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'type' => 'orm',
                'query' => [
                    'from' => [
                        ['table' => PaymentStatus::class, 'alias' => 'ps']
                    ]
                ]
            ],
            'filters' => [
                'columns' => []
            ]
        ]);

        $originalConfig = $config->toArray();
        $event = $this->createBuildBeforeEvent(Report::GRID_PREFIX . 'empty_filters', $config);

        $this->listener->onBuildBefore($event);

        // Configuration should remain unchanged
        self::assertEquals($originalConfig, $config->toArray());
    }

    public function testOnBuildBeforeWithReportGridButNoPaymentStatusFilters(): void
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'type' => 'orm',
                'query' => [
                    'from' => [
                        ['table' => PaymentStatus::class, 'alias' => 'ps']
                    ]
                ]
            ],
            'filters' => [
                'columns' => [
                    'string_filter' => [
                        'type' => 'string',
                        'options' => []
                    ],
                    'number_filter' => [
                        'type' => 'number',
                        'options' => []
                    ]
                ]
            ]
        ]);

        $originalConfig = $config->toArray();
        $event = $this->createBuildBeforeEvent(Report::GRID_PREFIX . 'no_payment_filters', $config);

        $this->listener->onBuildBefore($event);

        // Configuration should remain unchanged
        self::assertEquals($originalConfig, $config->toArray());
    }

    public function testOnBuildBeforeWithSegmentGridAndPaymentStatusFilter(): void
    {
        $rootEntity = Order::class;

        $config = DatagridConfiguration::create([
            'source' => [
                'type' => 'orm',
                'query' => [
                    'from' => [
                        ['table' => $rootEntity, 'alias' => 'o']
                    ]
                ]
            ],
            'filters' => [
                'columns' => [
                    'payment_status' => [
                        'type' => PaymentStatusFilter::NAME,
                        'options' => []
                    ],
                    'other_filter' => [
                        'type' => 'string',
                        'options' => []
                    ]
                ]
            ]
        ]);

        $event = $this->createBuildBeforeEvent(Segment::GRID_PREFIX . 'test', $config);

        $this->listener->onBuildBefore($event);

        $configArray = $config->toArray();
        self::assertEquals(
            $rootEntity,
            $configArray['filters']['columns']['payment_status']['options']['target_entity']
        );

        // Verify other filters are not modified
        self::assertArrayNotHasKey(
            'target_entity',
            $configArray['filters']['columns']['other_filter']['options']
        );
    }

    public function testOnBuildBeforeWithNonReportOrSegmentGrid(): void
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'type' => 'orm',
                'query' => [
                    'from' => [
                        ['table' => PaymentStatus::class, 'alias' => 'ps']
                    ]
                ]
            ],
            'filters' => [
                'columns' => [
                    'payment_status' => [
                        'type' => PaymentStatusFilter::NAME,
                        'options' => []
                    ]
                ]
            ]
        ]);

        $originalConfig = $config->toArray();

        $event = $this->createBuildBeforeEvent('regular_grid', $config);

        $this->listener->onBuildBefore($event);

        // Configuration should remain unchanged
        self::assertEquals($originalConfig, $config->toArray());
    }

    public function testOnBuildBeforeWithSegmentGridButNoFilters(): void
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'type' => 'orm',
                'query' => [
                    'from' => [
                        ['table' => PaymentStatus::class, 'alias' => 'ps']
                    ]
                ]
            ]
        ]);
        $originalConfig = $config->toArray();

        $event = $this->createBuildBeforeEvent(Segment::GRID_PREFIX . 'no_filters', $config);

        $this->listener->onBuildBefore($event);

        // Configuration should remain unchanged
        self::assertEquals($originalConfig, $config->toArray());
    }

    public function testOnBuildBeforeWithSegmentGridButEmptyFilterColumns(): void
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'type' => 'orm',
                'query' => [
                    'from' => [
                        ['table' => PaymentStatus::class, 'alias' => 'ps']
                    ]
                ]
            ],
            'filters' => [
                'columns' => []
            ]
        ]);

        $originalConfig = $config->toArray();
        $event = $this->createBuildBeforeEvent(Segment::GRID_PREFIX . 'empty_filters', $config);

        $this->listener->onBuildBefore($event);

        // Configuration should remain unchanged
        self::assertEquals($originalConfig, $config->toArray());
    }

    public function testOnBuildBeforeWithMultiplePaymentStatusFiltersInSegmentGrid(): void
    {
        $rootEntity = Order::class;

        $config = DatagridConfiguration::create([
            'source' => [
                'type' => 'orm',
                'query' => [
                    'from' => [
                        ['table' => $rootEntity, 'alias' => 'o']
                    ]
                ]
            ],
            'filters' => [
                'columns' => [
                    'payment_status1' => [
                        'type' => PaymentStatusFilter::NAME,
                        'options' => ['existing_option' => 'value']
                    ],
                    'payment_status2' => [
                        'type' => PaymentStatusFilter::NAME,
                        'options' => []
                    ]
                ]
            ]
        ]);

        $event = $this->createBuildBeforeEvent(Segment::GRID_PREFIX . 'multi_test', $config);

        $this->listener->onBuildBefore($event);

        $configArray = $config->toArray();

        // Both payment-status filters should have target_entity set
        self::assertEquals(
            $rootEntity,
            $configArray['filters']['columns']['payment_status1']['options']['target_entity']
        );
        self::assertEquals(
            $rootEntity,
            $configArray['filters']['columns']['payment_status2']['options']['target_entity']
        );

        // Existing options should be preserved
        self::assertEquals(
            'value',
            $configArray['filters']['columns']['payment_status1']['options']['existing_option']
        );
    }

    public function testOnBuildBeforeDoesNotModifyNonPaymentStatusFilters(): void
    {
        $rootEntity = Order::class;

        $config = DatagridConfiguration::create([
            'source' => [
                'type' => 'orm',
                'query' => [
                    'from' => [
                        ['table' => $rootEntity, 'alias' => 'o']
                    ]
                ]
            ],
            'filters' => [
                'columns' => [
                    'payment_status' => [
                        'type' => PaymentStatusFilter::NAME,
                        'options' => []
                    ],
                    'string_filter' => [
                        'type' => 'string',
                        'options' => ['existing' => 'value']
                    ],
                    'choice_filter' => [
                        'type' => 'choice',
                        'options' => ['choices' => ['a', 'b']]
                    ]
                ]
            ]
        ]);

        $originalStringFilter = $config->toArray()['filters']['columns']['string_filter'];
        $originalChoiceFilter = $config->toArray()['filters']['columns']['choice_filter'];

        $event = $this->createBuildBeforeEvent(Report::GRID_PREFIX . 'test', $config);

        $this->listener->onBuildBefore($event);

        $configArray = $config->toArray();

        // Payment status filter should be modified
        self::assertArrayHasKey(
            'target_entity',
            $configArray['filters']['columns']['payment_status']['options']
        );

        // Other filters should remain unchanged
        self::assertEquals(
            $originalStringFilter,
            $configArray['filters']['columns']['string_filter']
        );
        self::assertEquals(
            $originalChoiceFilter,
            $configArray['filters']['columns']['choice_filter']
        );
    }

    private function createBuildBeforeEvent(
        string $gridName,
        DatagridConfiguration $config
    ): BuildBefore {
        $parameters = new ParameterBag();
        $datagrid = new Datagrid($gridName, $config, $parameters);

        return new BuildBefore($datagrid, $config);
    }
}
