<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Provider\Dashboard;

use Carbon\Carbon;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\OrderBundle\Provider\Dashboard\SalesOrdersChartScaleProvider;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\OrdersCreatedAt as OrdersFixtures;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SalesOrdersChartScaleProviderTest extends WebTestCase
{
    private FilterDateRangeConverter $filterDateRangeConverter;

    private SalesOrdersChartScaleProvider $chartScaleProvider;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->filterDateRangeConverter = self::getContainer()
            ->get('oro_dashboard.widget_config_value.date_range.converter');
        $this->chartScaleProvider = self::getContainer()
            ->get('oro_order.provider.dashboard.sales_orders_chart_scale_provider');
    }

    public function testGetScaleTypeNoDateRange1WidgetOption(): void
    {
        $this->expectExceptionObject(
            new \InvalidArgumentException('Date range 1 widget option should be specified.')
        );

        $this->chartScaleProvider->getScaleType(new WidgetOptionBag());
    }

    /**
     * @dataProvider getScaleTypeDataProvider
     */
    public function testGetScaleType(
        array $dateRanges,
        string $expectedScaleType,
        array $fixtures = []
    ): void {
        $this->setCurrentDate();

        $this->loadFixtures($fixtures);

        $convertedDateRanges = $this->getConvertedDateRanges($dateRanges);

        self::assertEquals(
            $expectedScaleType,
            $this->chartScaleProvider->getScaleType(new WidgetOptionBag($convertedDateRanges))
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getScaleTypeDataProvider(): array
    {
        $this->setCurrentDate();

        $today = Carbon::today(new \DateTimeZone('UTC'));

        $allTimeDateRange = [
            'dateRange1' => [
                'value' => [
                    'start' => null,
                    'end' => null,
                ],
                'type' => AbstractDateFilterType::TYPE_ALL_TIME,
            ],
        ];

        return [
            'today - hours' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_TODAY,
                    ],
                ],
                'expectedScaleType' => 'time',
            ],
            'Month-To-Date - days' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_THIS_MONTH,
                    ],
                ],
                'expectedScaleType' => 'day',
            ],
            'Quarter-To-Date - weeks' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_THIS_QUARTER,
                    ]
                ],
                'expectedScaleType' => 'date',
            ],
            'Year-To-Date - weeks' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => null,
                            'end' => null,
                        ],
                        'type' => AbstractDateFilterType::TYPE_THIS_YEAR,
                    ],
                ],
                'expectedScaleType' => 'date',
            ],
            'Custom 1 day - hours' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => clone $today,
                            'end' => clone $today,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                ],
                'expectedScaleType' => 'time',
            ],
            'Custom < 31 days - days' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $today)->subDays(14),
                            'end' => clone $today,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                ],
                'expectedScaleType' => 'day',
            ],
            'Custom 31 days - days' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $today)->subDays(30),
                            'end' => clone $today,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                ],
                'expectedScaleType' => 'day',
            ],
            'Custom => 31 days and < 53 weeks - weeks' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $today)->subWeeks(52),
                            'end' => clone $today,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                ],
                'expectedScaleType' => 'date',
            ],
            'Custom > 53 weeks - month' => [
                'dateRanges' => [
                    'dateRange1' => [
                        'value' => [
                            'start' => (clone $today)->subWeeks(54),
                            'end' => clone $today,
                        ],
                        'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    ],
                ],
                'expectedScaleType' => 'month',
            ],
            'All time: 1 day - hours' => [
                'dateRanges' => $allTimeDateRange,
                'expectedScaleType' => 'time',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration1Day::class,
                ],
            ],
            'All time: < 31 days - days' => [
                'dateRanges' => $allTimeDateRange,
                'expectedScaleType' => 'day',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration30Days::class,
                ],
            ],
            'All time: 31 days - days' => [
                'dateRanges' => $allTimeDateRange,
                'expectedScaleType' => 'day',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration31Days::class,
                ],
            ],
            'All time: > 31 days and < 53 weeks - weeks' => [
                'dateRanges' => $allTimeDateRange,
                'expectedScaleType' => 'date',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration52Weeks::class,
                ],
            ],
            'All time: > 53 weeks - month' => [
                'dateRanges' => $allTimeDateRange,
                'expectedScaleType' => 'month',
                'fixtures' => [
                    OrdersFixtures\LoadOrdersCreatedAtRangeDuration54Weeks::class,
                ],
            ],
        ];
    }

    private function setCurrentDate(): void
    {
        Carbon::setTestNow(Carbon::create(2023, 01, 04, 12, 0, 0, new \DateTimeZone('UTC')));
    }

    private function getConvertedDateRanges(array $dateRanges): array
    {
        $commonConfig = [
            'converter_attributes' => [
                'today_as_end_date_for' => [
                    'TYPE_THIS_WEEK',
                    'TYPE_THIS_MONTH',
                    'TYPE_THIS_QUARTER',
                    'TYPE_THIS_YEAR',
                    'TYPE_ALL_TIME',
                ],
            ],
        ];
        $dateRange1Config = array_merge_recursive(
            $commonConfig,
            [
                'converter_attributes' => [
                    'today_as_end_date_for' => ['TYPE_MORE_THAN'],
                ],
            ],
        );
        $convertedDateRanges = [];
        foreach ($dateRanges as $key => $dateRange) {
            $convertedDateRanges[$key] = $this->filterDateRangeConverter->getConvertedValue(
                [],
                $dateRange,
                $key === 'dateRange1' ? $dateRange1Config : $commonConfig
            );
        }

        return $convertedDateRanges;
    }
}
