<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider\Dashboard;

use Oro\Bundle\ChartBundle\Factory\ChartViewBuilderFactory;
use Oro\Bundle\ChartBundle\Model\ChartView;
use Oro\Bundle\ChartBundle\Model\ChartViewBuilder;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\OrderBundle\Provider\Dashboard\SalesOrdersChartDataProvider;
use Oro\Bundle\OrderBundle\Provider\Dashboard\SalesOrdersChartScaleProvider;
use Oro\Bundle\OrderBundle\Provider\Dashboard\SalesOrdersChartWidgetProvider;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesOrdersChartWidgetProviderTest extends TestCase
{
    private const CHART_NAME = 'chart_name';
    private const WIDGET_NAME = 'widget_name';

    private SalesOrdersChartDataProvider|MockObject $salesOrdersChartDataProvider;

    private SalesOrdersChartScaleProvider $chartScaleProvider;

    private ChartViewBuilderFactory|MockObject $chartViewBuilderFactory;

    private WidgetConfigs|MockObject $widgetConfigs;

    private SalesOrdersChartWidgetProvider $salesOrdersChartWidgetProvider;

    protected function setUp(): void
    {
        $this->salesOrdersChartDataProvider = $this->createMock(SalesOrdersChartDataProvider::class);
        $this->chartScaleProvider = $this->createMock(SalesOrdersChartScaleProvider::class);
        $this->chartViewBuilderFactory = $this->createMock(ChartViewBuilderFactory::class);
        $this->widgetConfigs = $this->createMock(WidgetConfigs::class);

        $this->salesOrdersChartWidgetProvider = new SalesOrdersChartWidgetProvider(
            $this->chartScaleProvider,
            $this->chartViewBuilderFactory,
            $this->widgetConfigs,
            $this->salesOrdersChartDataProvider,
            self::CHART_NAME,
            self::WIDGET_NAME
        );
    }

    /**
     * @dataProvider getChartWidgetDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetChartWidget(array $dateRanges, array $salesOrdersVolumeData, array $expected): void
    {
        $defaultIncludedOrderStatuses = [
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
            OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
            OrderStatusesProviderInterface::INTERNAL_STATUS_ARCHIVED
        ];

        $widgetOptions = [
            'includedOrderStatuses' => $defaultIncludedOrderStatuses,
            'includeSubOrders' => true,
        ];
        $dateRangeNumber = 1;
        foreach ($dateRanges as $dateRange) {
            $widgetOptions["dateRange$dateRangeNumber"] = $dateRange;
            $dateRangeNumber++;
        }
        $widgetOptions = new WidgetOptionBag($widgetOptions);

        $chartWidgetData = [
            'widgetName' => self::WIDGET_NAME,
            'widgetConfiguration' => [
                'title' => [
                    'value' => 'title',
                    // ...
                ],
                'dateRange1' => [
                    'value' => 'Jan 23, 2023 - Jan 24, 2023',
                    // ...
                ],
                'dateRange2' => [
                    // ...
                ],
                'dateRange3' => [
                    // ...
                ],
                'includedOrderStatuses' => [
                    'value' => 'Open; Shipped; Closed; Archived',
                    // ...
                ],
                'includedSubOrders' => [
                    'value' => '',
                    // ...
                ],
            ],
        ];
        $this->widgetConfigs->expects(self::once())
            ->method('getWidgetOptions')
            ->willReturn($widgetOptions);
        $this->widgetConfigs->expects(self::once())
            ->method('getWidgetAttributesForTwig')
            ->with(self::WIDGET_NAME)
            ->willReturn($chartWidgetData);

        $this->salesOrdersChartDataProvider->expects(self::once())
            ->method('getChartData')
            ->with($widgetOptions)
            ->willReturn($salesOrdersVolumeData);

        $viewType = 'day';
        $this->chartScaleProvider->expects(self::once())
            ->method('getScaleType')
            ->with($widgetOptions)
            ->willReturn($viewType);

        $chartViewBuilder = $this->createMock(ChartViewBuilder::class);
        $chartViewBuilder->expects(self::once())
            ->method('setArrayData')
            ->with($salesOrdersVolumeData['data'])
            ->willReturnSelf();

        $chartView = $this->createMock(ChartView::class);
        $chartViewBuilder->expects(self::once())
            ->method('getView')
            ->willReturn($chartView);

        $this->chartViewBuilderFactory->expects(self::once())
            ->method('createChartViewBuilder')
            ->with(
                self::CHART_NAME,
                'overlaid_multiline_chart',
                $viewType
            )
            ->willReturn($chartViewBuilder);

        $result = $this->salesOrdersChartWidgetProvider->getChartWidget();

        $chartWidgetData['chartView'] = $chartView;
        $expectedChartWidgetData = ArrayUtil::arrayMergeRecursiveDistinct($chartWidgetData, $expected);

        self::assertSame($expectedChartWidgetData, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getChartWidgetDataProvider(): array
    {
        $dateRange1 = [
            'value' => [
                'start' => new \DateTime('Jan 23', new \DateTimeZone('UTC')),
                'end' => new \DateTime('Jan 24', new \DateTimeZone('UTC')),
            ],
            'type' => AbstractDateFilterType::TYPE_BETWEEN,
        ];
        $dateRange2 = [
            'value' => [
                'start' => null,
                'end' => null,
            ],
            'type' => AbstractDateFilterType::TYPE_ALL_TIME,
        ];
        $dateRange3 = [
            'value' => [
                'start' => new \DateTime('Jan 24', new \DateTimeZone('UTC')),
                'end' => null,
            ],
            'type' => AbstractDateFilterType::TYPE_MORE_THAN,
        ];

        $salesOrdersVolumeData = [
            'Jan 23, 2023 - Jan 24, 2023' => [
                [
                    'date' => '2023-01-23',
                    'amount' => '100',
                ],
                [
                    'date' => '2023-01-24',
                    'amount' => '200',
                ],
            ],
            'Jan 24, 2023 - Jan 25, 2023' => [
                [
                    'date' => '2023-01-24',
                    'amount' => '200',
                ],
                [
                    'date' => '2023-01-25',
                    'amount' => '300',
                ],
            ],
            'Jan 20, 2023 - Jan 21, 2023' => [],
        ];

        return [
            'with non-empty date ranges' => [
                'dateRanges' => [
                    $dateRange1,
                    $dateRange2,
                    $dateRange3,
                ],
                'salesOrderVolumeData' => [
                    'data' => $salesOrdersVolumeData,
                    'calculatedDateRangeLabels' => [
                        'Jan 23, 2023 - Jan 24, 2023',
                        'Jan 24, 2023 - Jan 25, 2023',
                        'Jan 20, 2023 - Jan 21, 2023',
                    ],
                ],
                'expected' => [
                    // We should display actual date ranges calculated based on dateRange1
                    'widgetConfiguration' => [
                        'dateRange1' => [
                            'value' => 'Jan 23, 2023 - Jan 24, 2023',
                        ],
                        'dateRange2' => [
                            'value' => 'Jan 24, 2023 - Jan 25, 2023',
                        ],
                        'dateRange3' => [
                            'value' => 'Jan 20, 2023 - Jan 21, 2023',
                        ],
                    ],
                ],
            ],
            'empty date ranges are marked with show_on_widget=false' => [
                'dateRanges' => [
                    $dateRange1,
                    ['value' => null, 'type' => AbstractDateFilterType::TYPE_NONE],
                    ['value' => null, 'type' => AbstractDateFilterType::TYPE_NONE],
                ],
                'salesOrderVolumeData' => [
                    'data' => [key($salesOrdersVolumeData) => reset($salesOrdersVolumeData)],
                    'calculatedDateRangeLabels' => [
                        'Jan 23, 2023 - Jan 24, 2023',
                    ],
                ],
                'expected' => [
                    'widgetConfiguration' => [
                        'dateRange1' => [
                            'value' => 'Jan 23, 2023 - Jan 24, 2023',
                        ],
                        'dateRange2' => [
                            'show_on_widget' => false,
                        ],
                        'dateRange3' => [
                            'show_on_widget' => false,
                        ],
                    ],
                ],
            ],
        ];
    }
}
