<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ReportGridControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadOrderLineItemData::class,
            ]
        );

        $this->updateOrderDates();
        $this->createCalendarDates();
    }

    public function testBestSellingProductsWithFilters()
    {
        $content = $this->requestGrid('');
        $result = $this->jsonToArray($content);

        $this->assertCount(5, $result['data']);

        static::assertStringContainsString('10 Liters', $content);
        static::assertStringContainsString('15 Liters', $content);
        static::assertStringContainsString('35 Liters', $content);
        static::assertStringContainsString('40 Liters', $content);
        static::assertStringContainsString('"timePeriod":"2-1-2000"', $content);
        static::assertStringContainsString('"timePeriod":"3-1-2000"', $content);
        static::assertStringContainsString('"timePeriod":"2-2-2000"', $content);
    }

    public function testBestSellingProductsWithFiltersGroupedByMonth()
    {
        $content = $this->requestGrid('month');
        $result = $this->jsonToArray($content);

        $this->assertCount(4, $result['data']);

        static::assertStringContainsString('25 Liters', $content);
        static::assertStringContainsString('35 Liters', $content);
        static::assertStringContainsString('40 Liters', $content);
        static::assertStringContainsString('"timePeriod":"1-2000"', $content);
        static::assertStringContainsString('"timePeriod":"2-2000"', $content);
    }

    public function testBestSellingProductsWithFiltersGroupedByQuarter()
    {
        $content = $this->requestGrid('quarter');
        $result = $this->jsonToArray($content);

        $this->assertCount(3, $result['data']);

        static::assertStringContainsString('40 Liters', $content);
        static::assertStringContainsString('60 Liters', $content);
        static::assertStringContainsString('"timePeriod":"1-2000"', $content);
    }

    public function testBestSellingProductsWithFiltersGroupedByYear()
    {
        $content = $this->requestGrid('year');
        $result = $this->jsonToArray($content);

        $this->assertCount(3, $result['data']);

        static::assertStringContainsString('40 Liters', $content);
        static::assertStringContainsString('"timePeriod":"2000"', $content);
    }

    public function testBestSellingProductsWithFiltersGroupedByYearAndNoDates()
    {
        $content = $this->requestGrid('year', '2000-01-01 00:00', '2000-01-01 23:59');

        $result = $this->jsonToArray($content);

        $this->assertCount(3, $result['data']);
    }

    /**
     * @param string $groupingBy
     * @param string $periodStart
     * @param string $periodEnd
     * @return string
     */
    protected function requestGrid($groupingBy, $periodStart = '2000-01-01 00:00', $periodEnd = '2000-12-31 00:00')
    {
        $response = $this->client->requestGrid(
            'best-selling-products',
            [
                'best-selling-products[_filter][createdAt][type]' => 1,
                'best-selling-products[_filter][createdAt][part]' => 'value',
                'best-selling-products[_filter][createdAt][value][start]' => $periodStart,
                'best-selling-products[_filter][createdAt][value][end]' => $periodEnd,
                'best-selling-products[_filter][grouping][value]' => $groupingBy,
                'best-selling-products[_filter][sku][type]' => 1,
                'best-selling-products[_filter][sku][value]' => 'product',
                'best-selling-products[_sort_by][timePeriod]' => 'DESC',
                'best-selling-products[_sort_by][sku]' => 'DESC',
            ]
        );

        $this->assertJsonResponseStatusCodeEquals($response, 200);

        return $response->getContent();
    }

    protected function updateOrderDates()
    {
        $this->getReference(LoadOrders::ORDER_2)->setCreatedAt($this->createDate('2000-01-02'));
        $this->getReference(LoadOrders::ORDER_3)->setCreatedAt($this->createDate('2000-01-03'));
        $this->getReference(LoadOrders::ORDER_4)->setCreatedAt($this->createDate('2000-02-02'));
        $this->getReference(LoadOrders::ORDER_5)->setCreatedAt($this->createDate('2000-02-02'));
        $this->getReference(LoadOrders::ORDER_6)->setCreatedAt($this->createDate('2000-02-02'));

        $this->getContainer()->get('doctrine')->getManagerForClass(Order::class)->flush();
    }

    protected function createCalendarDates()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(CalendarDate::class);

        $dates = ['2000-01-02', '2000-01-03', '2000-02-02'];
        foreach ($dates as $date) {
            $calendarDate = new CalendarDate();
            $calendarDate->setDate($this->createDate($date));
            $em->persist($calendarDate);
        }
        $em->flush();
    }

    /**
     * @param string $date
     * @return \DateTime
     */
    protected function createDate($date)
    {
        return new \DateTime($date, new \DateTimeZone('UTC'));
    }
}
