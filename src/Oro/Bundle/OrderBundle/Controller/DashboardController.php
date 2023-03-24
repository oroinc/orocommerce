<?php

namespace Oro\Bundle\OrderBundle\Controller;

use Oro\Bundle\OrderBundle\Provider\Dashboard\SalesOrdersChartWidgetProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles dashboard actions logic.
 */
class DashboardController
{
    private SalesOrdersChartWidgetProvider $salesOrdersVolumeChartWidgetProvider;

    private SalesOrdersChartWidgetProvider $salesOrdersNumberChartWidgetProvider;

    public function __construct(
        SalesOrdersChartWidgetProvider $salesOrdersVolumeChartWidgetProvider,
        SalesOrdersChartWidgetProvider $salesOrdersNumberChartWidgetProvider
    ) {
        $this->salesOrdersVolumeChartWidgetProvider = $salesOrdersVolumeChartWidgetProvider;
        $this->salesOrdersNumberChartWidgetProvider = $salesOrdersNumberChartWidgetProvider;
    }

    /**
     * @Route(
     *      "/sales-orders-volume",
     *      name="oro_order_dashboard_sales_orders_volume",
     *      requirements={"widget"="[\w_-]+"}
     * )
     * @Template("@OroOrder/Dashboard/sales_orders_volume.html.twig")
     */
    public function salesOrderVolumeAction(): array
    {
        return $this->salesOrdersVolumeChartWidgetProvider->getChartWidget();
    }

    /**
     * @Route(
     *      "/sales-orders-number",
     *      name="oro_order_dashboard_sales_orders_number",
     *      requirements={"widget"="[\w_-]+"}
     * )
     * @Template("@OroOrder/Dashboard/sales_orders_number.html.twig")
     */
    public function salesOrderNumberAction(): array
    {
        return $this->salesOrdersNumberChartWidgetProvider->getChartWidget();
    }
}
