<?php

namespace Oro\Bundle\OrderBundle\Provider\Dashboard;

use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Provides Sales Orders chart data suitable for use in {@see SalesOrdersChartWidgetProvider}
 */
class SalesOrdersChartDataProvider
{
    private DateTimeFormatterInterface $dateTimeFormatter;

    private DateHelper $dateHelper;

    private SalesOrdersDataProviderInterface $salesOrdersDataProvider;

    private string $dataKey;

    public function __construct(
        DateTimeFormatterInterface $dateTimeFormatter,
        DateHelper $dateHelper,
        SalesOrdersDataProviderInterface $salesOrdersDataProvider,
        string $dataKey
    ) {
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->dateHelper = $dateHelper;
        $this->salesOrdersDataProvider = $salesOrdersDataProvider;
        $this->dataKey = $dataKey;
    }

    /**
     * @param string $scaleType - this value is calculated by {@see DateHelper::getScaleType}
     *
     * @return array<string, array{array{
     *     date: string,
     *     amount: string
     * }}>
     *  [
     *      // date range => array of arrays with dates and values
     *      'Dec 5, 2022 - Jan 4, 2023' => [
     *          [
     *              'date' => '2022-12-05',
     *          ],
     *          //...
     *          [
     *              'date' => '2022-12-05',
     *              'value' => '123.0000'
     *          ],
     *      ],
     *      //...
     *  ]
     */
    public function getChartData(WidgetOptionBag $widgetOptions, string $scaleType): array
    {
        $dateRanges = array_filter([
            $widgetOptions->get('dateRange1'),
            $widgetOptions->get('dateRange2'),
            $widgetOptions->get('dateRange3')
        ]);

        if (count($dateRanges) < 1) {
            throw new \InvalidArgumentException('At least one date range should be specified.');
        }

        $data = [];
        $calculatedDateRangeLabels = [];
        foreach (array_values($dateRanges) as $index => $dateRange) {
            if ($dateRange['type'] === AbstractDateFilterType::TYPE_NONE) {
                continue;
            }

            [$dateFrom, $dateTo] = $this->dateHelper->getPeriod($dateRange, Order::class, 'createdAt', true);

            if ($index === 0) {
                $interval = \DateInterval::createFromDateString(
                    sprintf('%s seconds', $dateFrom->diffInRealSeconds($dateTo))
                );
            } elseif (in_array(
                $dateRange['type'],
                [
                    AbstractDateFilterType::TYPE_MORE_THAN,
                    AbstractDateFilterType::TYPE_ALL_TIME,
                ],
                true
            )) {
                $dateTo = (clone $dateFrom)->add($interval);
                $dateRange['last_second_modifier'] = \DateInterval::createFromDateString('1 day');
            } else {
                $dateFrom = (clone $dateTo)->sub($interval);
            }

            $salesOrdersData = $this->salesOrdersDataProvider->getData($dateFrom, $dateTo, $widgetOptions, $scaleType);

            $dateToViewValue = clone $dateTo;
            if (isset($dateRange['last_second_modifier'])) {
                /**
                 * Gets original end date value after the problem "last second of the day" is solved
                 * {@see FilterDateRangeConverter::getConvertedValue}
                 */

                $dateToViewValue = (clone $dateTo)?->sub(\DateInterval::createFromDateString('1 second'));
            }

            $items = $this->dateHelper->convertToCurrentPeriod(
                $dateFrom,
                $dateToViewValue,
                $salesOrdersData,
                $this->dataKey,
                $this->dataKey,
                true,
                $scaleType
            );

            $periodLabel = $this->createPeriodLabel($dateFrom, $dateToViewValue);
            if (!array_key_exists($periodLabel, $data)) {
                $data[$periodLabel] = $items;
            }

            $calculatedDateRangeLabels[] = $periodLabel;
        }

        return [
            'data' => $data,
            'calculatedDateRangeLabels' => $calculatedDateRangeLabels,
        ];
    }

    private function createPeriodLabel(\DateTime $from, \DateTime $to): string
    {
        return sprintf(
            '%s - %s',
            $this->dateTimeFormatter->formatDate($from),
            $this->dateTimeFormatter->formatDate($to)
        );
    }
}
