<?php

namespace Oro\Bundle\OrderBundle\Provider\Dashboard;

use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Provides a scale for `sales_orders_volume_chart` and `sales_orders_number_chart` charts
 */
class SalesOrdersChartScaleProvider
{
    private DateHelper $dateHelper;

    public function __construct(DateHelper $dateHelper)
    {
        $this->dateHelper = $dateHelper;
    }

    /**
     * The charts should use Date Range 1 to determine the scale.
     *
     * @param WidgetOptionBag $widgetOptions
     *
     * @return string
     */
    public function getScaleType(WidgetOptionBag $widgetOptions): string
    {
        if (!$widgetOptions->has('dateRange1')) {
            throw new \InvalidArgumentException('Date range 1 widget option should be specified.');
        }

        $dateRange = $widgetOptions->get('dateRange1');
        [$startDate, $endDate] = $this->dateHelper->getPeriod($dateRange, Order::class, 'createdAt', true);

        return $this->dateHelper->getScaleType($startDate, $endDate, $dateRange['type']);
    }
}
