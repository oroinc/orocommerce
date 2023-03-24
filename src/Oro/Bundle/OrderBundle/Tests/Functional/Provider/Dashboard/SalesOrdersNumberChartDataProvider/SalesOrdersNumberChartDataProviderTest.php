<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Provider\Dashboard\SalesOrdersNumberChartDataProvider;

use Carbon\Carbon;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\OrderBundle\Provider\Dashboard\SalesOrdersChartDataProvider;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\Provider\Dashboard\AbstractBasicSalesOrdersChartDataProviderTest;

/**
 * @dbIsolationPerTest
 */
class SalesOrdersNumberChartDataProviderTest extends AbstractBasicSalesOrdersChartDataProviderTest
{
    protected function getSalesOrdersChartDataProvider(): SalesOrdersChartDataProvider
    {
        return self::getContainer()
            ->get('oro_order.provider.dashboard.sales_orders_chart_data_provider.number');
    }

    public function testGetChartDataWithoutDateRanges(): void
    {
        $this->expectExceptionObject(
            new \InvalidArgumentException('At least one date range should be specified.')
        );

        $this->salesOrdersChartDataProvider->getChartData(new WidgetOptionBag(), 'day');
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getChartDataDataProvider(): array
    {
        $this->setCurrentDate();

        $currentDate = Carbon::today(new \DateTimeZone('UTC'));

        return [
            'Scaling of X-axis (Today - hours)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_TODAY,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'scaling/today.yml',
            ],
            'Scaling of X-axis (Month-To-Date - days)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_THIS_MONTH,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'scaling/monthToDate.yml',
            ],
            'Scaling of X-axis (Quarter-To-Date - weeks)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_THIS_QUARTER,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'scaling/quarterToDate.yml',
            ],
            'Scaling of X-axis (Year-To-Date - weeks)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_THIS_YEAR,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'scaling/yearToDate.yml',
            ],
            'Scaling of X-axis (Custom 1 day - days)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => clone $currentDate,
                            'end' => clone $currentDate,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'scaling/customOneDay.yml',
            ],
            'Scaling of X-axis (Custom < 31 days - days)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(29),
                            'end' => clone $currentDate,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'scaling/custom30Days.yml',
            ],
            'Scaling of X-axis (Custom 31 days - days)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(30),
                            'end' => clone $currentDate,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'scaling/custom31Days.yml',
            ],
            'Scaling of X-axis (Custom => 31 days and < 53 weeks - weeks)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->subWeeks(51),
                            'end' => clone $currentDate,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'scaling/custom52Weeks.yml',
            ],
            'Scaling of X-axis (Custom - 53 weeks - weeks)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(370),
                            'end' => clone $currentDate,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'scaling/custom53Weeks.yml',
            ],
            'Scaling of X-axis (Custom > 53 weeks - month)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->subWeeks(54),
                            'end' => clone $currentDate,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'scaling/custom54Weeks.yml',
            ],
            'Scaling of X-axis (later than)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(2),
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
                ],
                'expectedResultsPath' => 'scaling/laterThan.yml',
            ],
            'Scaling of X-axis (earlier than)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => clone $currentDate,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'scaling/earlierThan.yml',
            ],
            'Scaling of X-axis (empty dateRange1 - no empty array, dates should be present)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->addDays(3),
                            'end' => (clone $currentDate)->addDays(5),
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => null,
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'scaling/noDataForDateRange1.yml',
            ],
            'chart should use Date Range 1 to determine the scale' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_TODAY,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(8),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(3),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'scaling/scaleByDateRange1.yml',
            ],
            'the same ranges' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(29),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(29),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(29),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'general/sameRanges.yml',
            ],
            'all order statuses' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(29),
                            'end' => clone $currentDate,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(29),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(29),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => array_merge(
                        self::DEFAULT_INCLUDED_ORDER_STATUSES,
                        [
                            OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                        ]
                    ),
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'general/allOrderStatuses.yml',
            ],
            'with sub-orders' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(29),
                            'end' => clone $currentDate,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(29),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(29),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => true,
                ],
                'expectedResultsPath' => 'general/withSubOrders.yml',
            ],
            'allTime for dateRange2 and dateRange3' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(4),
                            'end' => (clone $currentDate),
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'allTime/allTimeForRange2AndRange3.yml',
            ],
            'chart should use Date Range 1 to determine the scale (equals ranges)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(4),
                            'end' => (clone $currentDate),
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(4),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(4),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'combo/31-12-2022_-_05-01-2023,laterThan31-12-2022,laterThan31-12-2022.yml',
            ],
            'between as later than' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(2),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(3),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(4),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'combo/laterThan02-01-2023,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'later than dates in future (swap dates)' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->addDays(5),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->addDays(3),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->addDays(8),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'combo/laterThanDatesInFuture.yml',
            ],
            'earlier than in dateRange1, later than in dateRange2 and dateRange3' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => clone $currentDate,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(3),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(4),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'combo/earlierThan04-01-2023,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'month-To-Date, later than' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_THIS_MONTH,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(3),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(4),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'combo/monthToDate,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'quarter-To-Date, laterThan' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_THIS_QUARTER,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(3),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(4),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'combo/quarterToDate,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'year-To-Date, laterThan' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_THIS_YEAR,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(3),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(4),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'combo/yearToDate,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            '01 february + 31 days' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(30),
                            'end' => clone $currentDate,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange2' => [
                        'value' => [
                            'start' => new \DateTime('01-02-2023', new \DateTimeZone('UTC')),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    ],
                    'dateRange3' => null,
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                ],
                'expectedResultsPath' => 'combo/01FebruaryPlus31Days.yml',
            ],
        ];
    }
}
