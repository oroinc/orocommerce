<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

/**
 * Limits the grid data by the organization the promotion was created from.
 */
class PromotionProductsCollectionGridListener
{
    public function onBuildAfter(BuildAfter $event): void
    {
        $dataGrid = $event->getDatagrid();

        $dataSource = $dataGrid->getDatasource();
        if (!$dataSource instanceof OrmDatasource) {
            return;
        }

        $parameters = $dataGrid->getParameters();
        $params = $parameters->get('params', []);
        $promotion = $params['promotion'];

        $organization = $promotion->getOrganization();
        $dataGridQueryBuilder = $dataSource->getQueryBuilder();
        $dataGridQueryBuilder->andWhere('IDENTITY(product.organization) = :orgId')
            ->setParameter('orgId', $organization);
    }
}
