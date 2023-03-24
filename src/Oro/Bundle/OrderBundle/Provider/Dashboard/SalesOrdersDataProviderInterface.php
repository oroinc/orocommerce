<?php

namespace Oro\Bundle\OrderBundle\Provider\Dashboard;

use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

/**
 * Interface that all Sales Orders data providers should implement.
 */
interface SalesOrdersDataProviderInterface
{
    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param WidgetOptionBag $widgetOptions
     * @param string $scaleType - this value is calculated by {@see DateHelper::getScaleType}
     *
     * @return array
     */
    public function getData(
        \DateTime $dateFrom,
        \DateTime $dateTo,
        WidgetOptionBag $widgetOptions,
        string $scaleType
    ): array;
}
