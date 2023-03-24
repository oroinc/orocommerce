<?php

namespace Oro\Bundle\OrderBundle\Provider\Dashboard;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;

/**
 * Provides Sales Orders Volume data suitable for use in {@see SalesOrdersChartDataProvider}
 */
class SalesOrdersVolumeDataProvider implements SalesOrdersDataProviderInterface
{
    private ManagerRegistry $registry;

    private ?OrderRepository $orderRepository = null;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param WidgetOptionBag $widgetOptions
     * @param string $scaleType - this value is calculated by {@see DateHelper::getScaleType}
     *
     * @return array<array{
     *     amount: int,
     *     yearCreated?: string,
     *     monthCreated?: string,
     *     weekCreated?: string,
     *     dayCreated?: string,
     *     dateCreated?: string,
     *     hourCreated?: string
     * }> - Array of arrays with dates and Sales Orders volume (amount) values
     *  [
     *      [
     *          'amount' => '123.0000',
     *          'yearCreated' => '2023',
     *          'monthCreated' => '1',
     *          'dayCreated' => '1',
     *      ],
     *      //...
     *  ]
     */
    public function getData(
        \DateTime $dateFrom,
        \DateTime $dateTo,
        WidgetOptionBag $widgetOptions,
        string $scaleType
    ): array {
        return $this->getOrderRepository()->getSalesOrdersVolume(
            $dateFrom,
            $dateTo,
            $widgetOptions->get('includedOrderStatuses', []),
            $widgetOptions->get('includeSubOrders'),
            $widgetOptions->get('orderTotal'),
            $scaleType
        );
    }

    private function getOrderRepository(): OrderRepository
    {
        if (!$this->orderRepository) {
            $this->orderRepository = $this->registry->getRepository(Order::class);
        }

        return $this->orderRepository;
    }
}
