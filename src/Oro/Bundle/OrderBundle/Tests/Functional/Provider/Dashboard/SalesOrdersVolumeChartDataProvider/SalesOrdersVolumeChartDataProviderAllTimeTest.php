<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Provider\Dashboard\SalesOrdersVolumeChartDataProvider;

use Carbon\Carbon;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\OrderBundle\Provider\Dashboard\SalesOrdersChartDataProvider;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\OrdersCreatedAt as OrdersFixtures;
use Oro\Bundle\OrderBundle\Tests\Functional\Provider\Dashboard\AbstractBasicSalesOrdersChartDataProviderTest;

/**
 * @dbIsolationPerTest
 */
class SalesOrdersVolumeChartDataProviderAllTimeTest extends AbstractBasicSalesOrdersChartDataProviderTest
{
    protected function getSalesOrdersChartDataProvider(): SalesOrdersChartDataProvider
    {
        return self::getContainer()
            ->get('oro_order.provider.dashboard.sales_orders_chart_data_provider.volume');
    }

    /**
     * @dataProvider getChartDataAllTimeNoResultsDataProvider
     */
    public function testGetChartDataAllTimeNoResults(string $orderTotal, string $expectedResultsPath): void
    {
        Carbon::setTestNow(Carbon::create(1901, 02, 01, 12, 0, 0, new \DateTimeZone('UTC')));

        $dateRanges = [
            'dateRange1' => [
                'value' => [
                    'start' => null,
                    'end' => null,
                ],
                'type' => AbstractDateFilterType::TYPE_ALL_TIME,
            ],
            'dateRange2' => null,
            'dateRange3' => null,
        ];
        $convertedDateRanges = $this->getConvertedDateRanges($dateRanges);
        $widgetOptions = new WidgetOptionBag(array_merge(
            $convertedDateRanges,
            [
                'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                'includeSubOrders' => false,
                'orderTotal' => $orderTotal,
            ]
        ));
        $scaleType = $this->chartScaleProvider->getScaleType($widgetOptions);

        $chartData = $this->salesOrdersChartDataProvider->getChartData($widgetOptions, $scaleType);

        $this->assertChartDataEqualsExpectedResults($expectedResultsPath, $chartData);
    }

    public function getChartDataAllTimeNoResultsDataProvider(): array
    {
        return [
            'subtotal' => [
                'orderTotal' => 'subtotal',
                'expectedResultsPath' => 'subtotal/allTime/noOrders.yml',
            ],
            'total' => [
                'orderTotal' => 'total',
                'expectedResultsPath' => 'total/allTime/noOrders.yml',
            ],
            'subtotal_with_discounts' => [
                'orderTotal' => 'subtotal_with_discounts',
                'expectedResultsPath' => 'subtotal_with_discounts/allTime/noOrders.yml',
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getChartDataDataProvider(): array
    {
        $this->setCurrentDate();

        $currentDate = Carbon::today(new \DateTimeZone('UTC'));

        $allTimeDateRange1 = [
            'dateRange1' => [
                'value' => [
                    'start' => null,
                    'end' => null,
                ],
                'type' => AbstractDateFilterType::TYPE_ALL_TIME,
            ],
            'dateRange2' => null,
            'dateRange3' => null,
        ];

        return [
            'Scaling of X-axis (All Time 1 day - days) with amount type subtotal' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/allTime/durationOneDay.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration1Day::class,
                ],
            ],
            'Scaling of X-axis (All Time < 31 days - days) with amount type subtotal' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/allTime/duration30Days.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration30Days::class,
                ],
            ],
            'Scaling of X-axis (All Time 31 days - days) with amount type subtotal' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/allTime/duration31Days.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration31Days::class,
                ],
            ],
            'Scaling of X-axis (All Time > 31 days and < 53 weeks - weeks) with amount type subtotal' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/allTime/duration52Weeks.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration52Weeks::class,
                ],
            ],
            'Scaling of X-axis (All Time - 53 weeks - weeks) with amount type subtotal' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/allTime/duration53Weeks.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration53Weeks::class,
                ],
            ],
            'Scaling of X-axis (All Time > 53 weeks - month) with amount type subtotal' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/allTime/duration54Weeks.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration54Weeks::class,
                ],
            ],
            'between with empty dates as all time with amount type subtotal' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/allTime/duration54Weeks.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration54Weeks::class,
                ],
            ],
            'all order statuses with amount type subtotal' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => array_merge(
                        self::DEFAULT_INCLUDED_ORDER_STATUSES,
                        [
                            OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
                        ]
                    ),
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/allTime/allOrderStatuses.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration30Days::class,
                ],
            ],
            'with sub-orders with amount type subtotal' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => true,
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/allTime/withSubOrders.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration30Days::class,
                ],
            ],
            'all time, later than, later than with amount type subtotal' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_ALL_TIME,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(32),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(8),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/allTime/allTime,laterThan,laterThan.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration30Days::class,
                ],
            ],
            'Scaling of X-axis (All Time 1 day - days) with amount type total' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/allTime/durationOneDay.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration1Day::class,
                ],
            ],
            'Scaling of X-axis (All Time < 31 days - days) with amount type total' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/allTime/duration30Days.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration30Days::class,
                ],
            ],
            'Scaling of X-axis (All Time 31 days - days) with amount type total' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/allTime/duration31Days.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration31Days::class,
                ],
            ],
            'Scaling of X-axis (All Time > 31 days and < 53 weeks - weeks) with amount type total' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/allTime/duration52Weeks.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration52Weeks::class,
                ],
            ],
            'Scaling of X-axis (All Time - 53 weeks - weeks) with amount type total' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/allTime/duration53Weeks.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration53Weeks::class,
                ],
            ],
            'Scaling of X-axis (All Time > 53 weeks - month) with amount type total' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/allTime/duration54Weeks.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration54Weeks::class,
                ],
            ],
            'between with empty dates as all time with amount type total' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/allTime/duration54Weeks.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration54Weeks::class,
                ],
            ],
            'all order statuses with amount type total' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => array_merge(
                        self::DEFAULT_INCLUDED_ORDER_STATUSES,
                        [
                            OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
                        ]
                    ),
                    'includeSubOrders' => false,
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/allTime/allOrderStatuses.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration30Days::class,
                ],
            ],
            'with sub-orders with amount type total' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => true,
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/allTime/withSubOrders.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration30Days::class,
                ],
            ],
            'all time, later than, later than with amount type total' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_ALL_TIME,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(32),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(8),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/allTime/allTime,laterThan,laterThan.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration30Days::class,
                ],
            ],
            'Scaling of X-axis (All Time 1 day - days) with amount type subtotal_with_discounts' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/allTime/durationOneDay.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration1Day::class,
                ],
            ],
            'Scaling of X-axis (All Time < 31 days - days) with amount type subtotal_with_discounts' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/allTime/duration30Days.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration30Days::class,
                ],
            ],
            'Scaling of X-axis (All Time 31 days - days) with amount type subtotal_with_discounts' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/allTime/duration31Days.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration31Days::class,
                ],
            ],
            'Scaling of X-axis (All Time > 31 days and < 53 weeks - weeks) with amount type subtotal_with_discounts' =>
                [
                    'dateRanges' => $allTimeDateRange1,
                    'widgetOptions' => [
                        'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                        'includeSubOrders' => false,
                        'orderTotal' => 'subtotal_with_discounts',
                    ],
                    'expectedResultsPath' => 'subtotal_with_discounts/allTime/duration52Weeks.yml',
                    'fixtures' => [
                        OrdersFixtures\LoadOrdersCreatedAtRangeDuration52Weeks::class,
                    ],
                ],
            'Scaling of X-axis (All Time - 53 weeks - weeks) with amount type subtotal_with_discounts' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/allTime/duration53Weeks.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration53Weeks::class,
                ],
            ],
            'Scaling of X-axis (All Time > 53 weeks - month) with amount type subtotal_with_discounts' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/allTime/duration54Weeks.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration54Weeks::class,
                ],
            ],
            'between with empty dates as all time with amount type subtotal_with_discounts' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/allTime/duration54Weeks.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration54Weeks::class,
                ],
            ],
            'all order statuses with amount type subtotal_with_discounts' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => array_merge(
                        self::DEFAULT_INCLUDED_ORDER_STATUSES,
                        [
                            OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
                        ]
                    ),
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/allTime/allOrderStatuses.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration30Days::class,
                ],
            ],
            'with sub-orders with amount type subtotal_with_discounts' => [
                'dateRanges' => $allTimeDateRange1,
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => true,
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/allTime/withSubOrders.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration30Days::class,
                ],
            ],
            'all time, later than, later than with amount type subtotal_with_discounts' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_ALL_TIME,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(32),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(8),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/allTime/allTime,laterThan,laterThan.yml',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration30Days::class,
                ],
            ],
        ];
    }
}
