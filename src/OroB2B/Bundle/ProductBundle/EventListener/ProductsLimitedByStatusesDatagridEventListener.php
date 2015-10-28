<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

class ProductsLimitedByStatusesDatagridEventListener
{
    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $dataSource */
        $dataSource = $event->getDatagrid()->getAcceptedDatasource();
        $qb = $dataSource->getQueryBuilder();
        //TODO: Limitation QueryBuilder by Modifier
    }
}
