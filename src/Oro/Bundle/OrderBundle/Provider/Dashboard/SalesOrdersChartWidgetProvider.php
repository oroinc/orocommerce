<?php

namespace Oro\Bundle\OrderBundle\Provider\Dashboard;

use Oro\Bundle\ChartBundle\Factory\ChartViewBuilderFactory;
use Oro\Bundle\ChartBundle\Model\ChartView;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;

/**
 * Provides Chart Widget data for `sales_orders_volume_chart` and `sales_orders_number_chart`
 * with scale type based on date ranges.
 */
class SalesOrdersChartWidgetProvider
{
    private SalesOrdersChartScaleProvider $chartScaleProvider;

    private ChartViewBuilderFactory $chartViewBuilderFactory;

    private WidgetConfigs $widgetConfigs;

    private SalesOrdersChartDataProvider $salesOrdersChartDataProvider;

    private string $chartName;

    private string $widgetName;

    public function __construct(
        SalesOrdersChartScaleProvider $chartScaleProvider,
        ChartViewBuilderFactory $chartViewBuilderFactory,
        WidgetConfigs $widgetConfigs,
        SalesOrdersChartDataProvider $salesOrdersChartDataProvider,
        string $chartName,
        string $widgetName
    ) {
        $this->chartScaleProvider = $chartScaleProvider;
        $this->chartViewBuilderFactory = $chartViewBuilderFactory;
        $this->widgetConfigs = $widgetConfigs;
        $this->salesOrdersChartDataProvider = $salesOrdersChartDataProvider;
        $this->chartName = $chartName;
        $this->widgetName = $widgetName;
    }

    /**
     * @return array{
     *     widgetName: string,
     *     chartView: ChartView::class,
     *     widgetConfiguration: array{
     *         dateRange1: array{
     *             value: string,
     *         },
     *         dateRange2: array{
     *             value: string,
     *         },
     *         dateRange3: array{
     *             value: string,
     *         },
     *     }
     * }
     */
    public function getChartWidget(): array
    {
        $widgetOptions = $this->widgetConfigs->getWidgetOptions();

        $scaleType = $this->chartScaleProvider->getScaleType($widgetOptions);
        /**
         * {@see SalesOrdersChartDataProvider::getChartData} method should be executed before
         * {@see WidgetConfigs::getWidgetAttributesForTwig} because we should interact
         * with end date that solves a problem "last second of the day"
         */
        $salesOrdersChartData = $this->salesOrdersChartDataProvider->getChartData($widgetOptions, $scaleType);

        $chartViewBuilder = $this->chartViewBuilderFactory->createChartViewBuilder(
            $this->chartName,
            'overlaid_multiline_chart',
            $scaleType
        );

        $chartView = $chartViewBuilder
            ->setArrayData($salesOrdersChartData['data'])
            ->getView();

        $chartWidgetData = $this->widgetConfigs->getWidgetAttributesForTwig($this->widgetName);
        $chartWidgetData['chartView'] = $chartView;

        if (isset($chartWidgetData['widgetConfiguration']) && is_array($chartWidgetData['widgetConfiguration'])) {
            foreach (['dateRange1', 'dateRange2', 'dateRange3'] as $dateRangeName) {
                if ($widgetOptions->get($dateRangeName)['type'] === AbstractDateFilterType::TYPE_NONE) {
                    $chartWidgetData['widgetConfiguration'][$dateRangeName]['show_on_widget'] = false;
                }
            }

            $dateRangeNumber = 1;
            foreach ($salesOrdersChartData['calculatedDateRangeLabels'] as $calculatedDateRange) {
                // We should display actual date ranges calculated based on dateRange1
                $chartWidgetData['widgetConfiguration']["dateRange$dateRangeNumber"]['value'] = $calculatedDateRange;
                $dateRangeNumber++;
            }
        }

        return $chartWidgetData;
    }
}
