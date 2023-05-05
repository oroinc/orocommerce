<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Provider\Dashboard;

use Carbon\Carbon;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\OrderBundle\Provider\Dashboard\SalesOrdersChartDataProvider;
use Oro\Bundle\OrderBundle\Provider\Dashboard\SalesOrdersChartScaleProvider;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\OrdersCreatedAt\LoadOrdersCreatedAtRangeDuration4Days;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractBasicSalesOrdersChartDataProviderTest extends WebTestCase
{
    protected const DEFAULT_INCLUDED_ORDER_STATUSES = [
        OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
        OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
        OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
        OrderStatusesProviderInterface::INTERNAL_STATUS_ARCHIVED
    ];

    protected FilterDateRangeConverter $filterDateRangeConverter;

    protected DateTimeFormatterInterface $dateTimeFormatter;

    protected SalesOrdersChartScaleProvider $chartScaleProvider;

    protected ?SalesOrdersChartDataProvider $salesOrdersChartDataProvider = null;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->filterDateRangeConverter = self::getContainer()
            ->get('oro_dashboard.widget_config_value.date_range.converter');
        $this->dateTimeFormatter = self::getContainer()->get('oro_locale.formatter.date_time');
        $this->chartScaleProvider = self::getContainer()
            ->get('oro_order.provider.dashboard.sales_orders_chart_scale_provider');

        $this->salesOrdersChartDataProvider = $this->getSalesOrdersChartDataProvider();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
    }

    /**
     * @dataProvider getChartDataDataProvider
     */
    public function testGetChartData(
        array $dateRanges,
        array $widgetOptions,
        string $expectedResultsPath,
        array $fixtures = [],
    ): void {
        $this->setCurrentDate();

        $defaultFixtures = [
            LoadOrdersCreatedAtRangeDuration4Days::class,
        ];
        $this->loadFixtures($fixtures ?: $defaultFixtures);

        $convertedDateRanges = $this->getConvertedDateRanges($dateRanges);
        $widgetOptionsBag = new WidgetOptionBag(
            array_merge(
                $convertedDateRanges,
                $widgetOptions,
            )
        );
        $scaleType = $this->chartScaleProvider->getScaleType($widgetOptionsBag);

        $chartData = $this->salesOrdersChartDataProvider->getChartData($widgetOptionsBag, $scaleType);

        $this->assertChartDataEqualsExpectedResults($expectedResultsPath, $chartData);
    }

    protected function getConvertedDateRanges(array $dateRanges): array
    {
        $dateRange1Config = [
            'converter_attributes' => [
                'today_as_end_date_for' => [
                    'TYPE_THIS_MONTH',
                    'TYPE_THIS_QUARTER',
                    'TYPE_THIS_YEAR',
                    'TYPE_ALL_TIME',
                    'TYPE_MORE_THAN',
                ],
                'default_selected' => AbstractDateFilterType::TYPE_THIS_MONTH,
            ],
        ];
        $dateRange2Config = [
            'converter_attributes' => [
                'default_selected' => AbstractDateFilterType::TYPE_NONE,
            ],
        ];
        $convertedDateRanges = [];
        foreach ($dateRanges as $key => $dateRange) {
            $convertedDateRanges[$key] = $this->filterDateRangeConverter->getConvertedValue(
                [],
                $dateRange,
                $key === 'dateRange1' ? $dateRange1Config : $dateRange2Config
            );
        }

        return $convertedDateRanges;
    }

    protected function assertChartDataEqualsExpectedResults(string $expectedResultsPath, array $actualResults): void
    {
        self::assertEquals($this->getExpectedResultsData($expectedResultsPath), $actualResults);
    }

    /**
     * Loads the expected results content and converts it to an array.
     *
     * @param string $expectedResultsFilename The file name or full file path to YAML template file or array
     *
     * @return array
     */
    protected function getExpectedResultsData(string $expectedResultsFilename): array
    {
        return Yaml::parse($this->loadData($expectedResultsFilename, $this->getResultsDataFolderName()));
    }

    /**
     * Loads the response content.
     */
    protected function loadData(string $fileName, string $folderName = null): string
    {
        if ($this->isRelativePath($fileName)) {
            $fileName = $this->getTestResourcePath($folderName, $fileName);
        }
        $file = self::getContainer()->get('file_locator')->locate($fileName);
        self::assertTrue(is_file($file), sprintf('File "%s" with expected content not found', $fileName));

        return file_get_contents($file);
    }

    protected function getResultsDataFolderName(): string
    {
        return 'results';
    }

    protected function setCurrentDate(): void
    {
        Carbon::setTestNow(Carbon::create(2023, 01, 04, 12, 0, 0, new \DateTimeZone('UTC')));
    }

    abstract public function getChartDataDataProvider(): array;

    abstract protected function getSalesOrdersChartDataProvider(): SalesOrdersChartDataProvider;
}
