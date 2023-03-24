<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider\Dashboard;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\OrderBundle\Provider\Dashboard\SalesOrdersNumberDataProvider;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesOrdersNumberDataProviderTest extends TestCase
{
    private OrderRepository|MockObject $orderRepository;

    private SalesOrdersNumberDataProvider $salesOrdersNumberDataProvider;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepository::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($this->orderRepository);

        $this->salesOrdersNumberDataProvider = new SalesOrdersNumberDataProvider($registry);
    }

    public function testGetData(): void
    {
        $today = new \DateTime('today', new \DateTimeZone('UTC'));

        $dateFrom = (clone $today)->modify('-5 days');
        $dateTo = clone $today;
        $includedOrderStatuses = [
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
        ];
        $isIncludeSubOrders = true;

        $widgetOptions = new WidgetOptionBag(
            [
                'dateRange1' => [],
                'dateRange2' => null,
                'dateRange3' => null,
                'includedOrderStatuses' => $includedOrderStatuses,
                'includeSubOrders' => $isIncludeSubOrders,
            ]
        );
        $scaleType = 'day';

        $salesOrdersNumberData = [
            [
                'number' => 123,
                'yearCreated' => '2023',
                'monthCreated' => '1',
                'dayCreated' => '1',
            ],
        ];
        $this->orderRepository->expects(self::exactly(2))
            ->method('getSalesOrdersNumber')
            ->with(
                $dateFrom,
                $dateTo,
                $includedOrderStatuses,
                $isIncludeSubOrders,
                $scaleType
            )
            ->willReturn($salesOrdersNumberData);

        self::assertSame(
            $salesOrdersNumberData,
            $this->salesOrdersNumberDataProvider->getData($dateFrom, $dateTo, $widgetOptions, $scaleType)
        );
        // Checks that we get repository only once
        self::assertSame(
            $salesOrdersNumberData,
            $this->salesOrdersNumberDataProvider->getData($dateFrom, $dateTo, $widgetOptions, $scaleType)
        );
    }
}
