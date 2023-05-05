<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Provider\Dashboard\SalesOrdersVolumeChartDataProvider;

use Carbon\Carbon;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\OrderBundle\Provider\Dashboard\SalesOrdersChartDataProvider;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\Provider\Dashboard\AbstractBasicSalesOrdersChartDataProviderTest;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class SalesOrdersVolumeChartDataProviderTest extends AbstractBasicSalesOrdersChartDataProviderTest
{
    protected function getSalesOrdersChartDataProvider(): SalesOrdersChartDataProvider
    {
        return self::getContainer()
            ->get('oro_order.provider.dashboard.sales_orders_chart_data_provider.volume');
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getChartDataDataProvider(): array
    {
        $this->setCurrentDate();

        $currentDate = Carbon::today(new \DateTimeZone('UTC'));

        return [
            'Scaling of X-axis (Today - hours) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/today.yml',
            ],
            'Scaling of X-axis (Month-To-Date - days) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/monthToDate.yml',
            ],
            'Scaling of X-axis (Quarter-To-Date - weeks) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/quarterToDate.yml',
            ],
            'Scaling of X-axis (Year-To-Date - weeks) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/yearToDate.yml',
            ],
            'Scaling of X-axis (Custom 1 day - days) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/customOneDay.yml',
            ],
            'Scaling of X-axis (Custom < 31 days - days) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/custom30Days.yml',
            ],
            'Scaling of X-axis (Custom 31 days - days) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/custom31Days.yml',
            ],
            'Scaling of X-axis (Custom => 31 days and < 53 weeks - weeks) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/custom52Weeks.yml',
            ],
            'Scaling of X-axis (Custom - 53 weeks - weeks) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/custom53Weeks.yml',
            ],
            'Scaling of X-axis (Custom > 53 weeks - month) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/custom54Weeks.yml',
            ],
            'Scaling of X-axis (later than) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/laterThan.yml',
            ],
            'Scaling of X-axis (earlier than) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/earlierThan.yml',
            ],
            'Scaling of X-axis (empty dateRange1 - no empty array, dates should be present) with amount type subtotal'
            => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/noDataForDateRange1.yml',
            ],
            'chart should use Date Range 1 to determine the scale with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/scaling/scaleByDateRange1.yml',
            ],
            'the same ranges with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/general/sameRanges.yml',
            ],
            'all order statuses with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/general/allOrderStatuses.yml',
            ],
            'with sub-orders with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/general/withSubOrders.yml',
            ],
            'allTime for dateRange2 and dateRange3 with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/allTime/allTimeForRange2AndRange3.yml',
            ],
            'chart should use Date Range 1 to determine the scale (equals ranges) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/combo/31-12-2022_-_05-01-2023,laterThan31-12-2022,today.yml',
            ],
            'between as later than with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' =>
                    'subtotal/combo/laterThan02-01-2023,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'later than dates in future (swap dates) with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/combo/laterThanDatesInFuture.yml',
            ],
            'earlier than in dateRange1, later than in dateRange2 and dateRange3 with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' =>
                    'subtotal/combo/earlierThan04-01-2023,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'month-To-Date, later than with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/combo/monthToDate,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'quarter-To-Date, later than with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/combo/quarterToDate,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'year-To-Date, later than with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/combo/yearToDate,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            '01 february + 31 days with amount type subtotal' => [
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
                    'orderTotal' => 'subtotal',
                ],
                'expectedResultsPath' => 'subtotal/combo/01FebruaryPlus31Days.yml',
            ],
            'Scaling of X-axis (Today - hours) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/today.yml',
            ],
            'Scaling of X-axis (Month-To-Date - days) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/monthToDate.yml',
            ],
            'Scaling of X-axis (Quarter-To-Date - weeks) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/quarterToDate.yml',
            ],
            'Scaling of X-axis (Year-To-Date - weeks) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/yearToDate.yml',
            ],
            'Scaling of X-axis (Custom 1 day - days) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/customOneDay.yml',
            ],
            'Scaling of X-axis (Custom < 31 days - days) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/custom30Days.yml',
            ],
            'Scaling of X-axis (Custom 31 days - days) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/custom31Days.yml',
            ],
            'Scaling of X-axis (Custom => 31 days and < 53 weeks - weeks) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/custom52Weeks.yml',
            ],
            'Scaling of X-axis (Custom - 53 weeks - weeks) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/custom53Weeks.yml',
            ],
            'Scaling of X-axis (Custom > 53 weeks - month) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/custom54Weeks.yml',
            ],
            'Scaling of X-axis (later than) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/laterThan.yml',
            ],
            'Scaling of X-axis (earlier than) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/earlierThan.yml',
            ],
            'Scaling of X-axis (empty dateRange1 - no empty array, dates should be present) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/noDataForDateRange1.yml',
            ],
            'chart should use Date Range 1 to determine the scale with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/scaling/scaleByDateRange1.yml',
            ],
            'the same ranges with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/general/sameRanges.yml',
            ],
            'all order statuses with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/general/allOrderStatuses.yml',
            ],
            'with sub-orders with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/general/withSubOrders.yml',
            ],
            'allTime for dateRange2 and dateRange3 with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/allTime/allTimeForRange2AndRange3.yml',
            ],
            'chart should use Date Range 1 to determine the scale (equals ranges) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/combo/31-12-2022_-_05-01-2023,laterThan31-12-2022,today.yml',
            ],
            'between as later than amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/combo/laterThan02-01-2023,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'later than dates in future (swap dates) with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/combo/laterThanDatesInFuture.yml',
            ],
            'earlier than in dateRange1, later than in dateRange2 and dateRange3 with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' =>
                    'total/combo/earlierThan04-01-2023,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'month-To-Date, later than with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/combo/monthToDate,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'quarter-To-Date, later than with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/combo/quarterToDate,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'year-To-Date, later than with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/combo/yearToDate,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            '01 february + 31 days with amount type total' => [
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
                    'orderTotal' => 'total',
                ],
                'expectedResultsPath' => 'total/combo/01FebruaryPlus31Days.yml',
            ],
            'Scaling of X-axis (Today - hours) with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/today.yml',
            ],
            'Scaling of X-axis (Month-To-Date - days) with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/monthToDate.yml',
            ],
            'Scaling of X-axis (Quarter-To-Date - weeks) with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/quarterToDate.yml',
            ],
            'Scaling of X-axis (Year-To-Date - weeks) with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/yearToDate.yml',
            ],
            'Scaling of X-axis (Custom 1 day - days) with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/customOneDay.yml',
            ],
            'Scaling of X-axis (Custom < 31 days - days) with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/custom30Days.yml',
            ],
            'Scaling of X-axis (Custom 31 days - days) with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/custom31Days.yml',
            ],
            'Scaling of X-axis (Custom => 31 days and < 53 weeks - weeks) with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/custom52Weeks.yml',
            ],
            'Scaling of X-axis (Custom - 53 weeks - weeks) with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/custom53Weeks.yml',
            ],
            'Scaling of X-axis (Custom > 53 weeks - month) with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/custom54Weeks.yml',
            ],
            'Scaling of X-axis (later than) with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/laterThan.yml',
            ],
            'Scaling of X-axis (earlier than) with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/earlierThan.yml',
            ],
            'Scaling of X-axis (empty dateRange1 - no empty array, dates should be present) 
            with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/noDataForDateRange1.yml',
            ],
            'chart should use Date Range 1 to determine the scale with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/scaling/scaleByDateRange1.yml',
            ],
            'the same ranges with amount type subtotal_with_discounts' => [
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
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                    'dateRange3' => [
                        'value' => [
                            'start' => (clone $currentDate)->subDays(29),
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                ],
                'widgetOptions' => [
                    'includedOrderStatuses' => self::DEFAULT_INCLUDED_ORDER_STATUSES,
                    'includeSubOrders' => false,
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/general/sameRanges.yml',
            ],
            'all order statuses with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/general/allOrderStatuses.yml',
            ],
            'with sub-orders with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/general/withSubOrders.yml',
            ],
            'allTime for dateRange2 and dateRange3 with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/allTime/allTimeForRange2AndRange3.yml',
            ],
            'chart should use Date Range 1 to determine the scale (equals ranges) 
            with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' =>
                    'subtotal_with_discounts/combo/31-12-2022_-_05-01-2023,laterThan31-12-2022,today.yml',
            ],
            'between as later than amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' =>
                    'subtotal_with_discounts/combo/laterThan02-01-2023,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'later than dates in future (swap dates) with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/combo/laterThanDatesInFuture.yml',
            ],
            'earlier than in dateRange1, later than in dateRange2 and dateRange3 
            with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' =>
                    'subtotal_with_discounts/combo/earlierThan04-01-2023,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'month-To-Date, later than with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' =>
                    'subtotal_with_discounts/combo/monthToDate,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'quarter-To-Date, later than with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' =>
                    'subtotal_with_discounts/combo/quarterToDate,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            'year-To-Date, later than with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' =>
                    'subtotal_with_discounts/combo/yearToDate,laterThan01-01-2023,laterThan31-12-2022.yml',
            ],
            '01 february + 31 days with amount type subtotal_with_discounts' => [
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
                    'orderTotal' => 'subtotal_with_discounts',
                ],
                'expectedResultsPath' => 'subtotal_with_discounts/combo/01FebruaryPlus31Days.yml',
            ],
        ];
    }
}
