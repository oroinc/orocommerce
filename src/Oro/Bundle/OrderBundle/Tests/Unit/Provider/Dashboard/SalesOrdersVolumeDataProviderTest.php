<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider\Dashboard;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\OrderBundle\Provider\Dashboard\SalesOrdersVolumeDataProvider;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesOrdersVolumeDataProviderTest extends TestCase
{
    private OrderRepository|MockObject $orderRepository;

    private SalesOrdersVolumeDataProvider $salesOrdersVolumeDataProvider;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepository::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($this->orderRepository);

        $this->salesOrdersVolumeDataProvider = new SalesOrdersVolumeDataProvider($registry);
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

        $amountType = 'total';
        $widgetOptions = new WidgetOptionBag(
            [
                'dateRange1' => [],
                'dateRange2' => null,
                'dateRange3' => null,
                'includedOrderStatuses' => $includedOrderStatuses,
                'includeSubOrders' => $isIncludeSubOrders,
                'orderTotal' => $amountType,
            ]
        );
        $scaleType = 'day';

        $salesOrdersVolumeData = [
            [
                'amount' => '123.0000',
                'yearCreated' => '2023',
                'monthCreated' => '1',
                'dayCreated' => '1',
            ],
        ];
        $this->orderRepository->expects(self::exactly(2))
            ->method('getSalesOrdersVolume')
            ->with(
                $dateFrom,
                $dateTo,
                $includedOrderStatuses,
                $isIncludeSubOrders,
                $amountType,
                $scaleType,
            )
            ->willReturn($salesOrdersVolumeData);

        self::assertSame(
            $salesOrdersVolumeData,
            $this->salesOrdersVolumeDataProvider->getData($dateFrom, $dateTo, $widgetOptions, $scaleType)
        );
        // Checks that we get repository only once
        self::assertSame(
            $salesOrdersVolumeData,
            $this->salesOrdersVolumeDataProvider->getData($dateFrom, $dateTo, $widgetOptions, $scaleType)
        );
    }
}
