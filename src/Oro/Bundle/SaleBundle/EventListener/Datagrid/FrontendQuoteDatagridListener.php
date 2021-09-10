<?php

namespace Oro\Bundle\SaleBundle\EventListener\Datagrid;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\SaleBundle\Entity\Quote;

/**
 * Appends Frontend Datagrid query with proper internal statuses.
 */
class FrontendQuoteDatagridListener
{
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $ormDataSource */
        $ormDataSource = $event->getDatagrid()->getDatasource();
        $this->applyFiltrationByInternalStatuses($ormDataSource->getQueryBuilder());
        $countQb = $ormDataSource->getCountQb();
        if ($countQb) {
            $this->applyFiltrationByInternalStatuses($countQb);
        }
    }

    protected function applyFiltrationByInternalStatuses(QueryBuilder $qb)
    {
        $rootAliases = $qb->getRootAliases();
        $field = sprintf('%s.internal_status', reset($rootAliases));

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->isNull($field),
                $qb->expr()->in($field, ':internalStatuses')
            )
        );

        $qb->setParameter('internalStatuses', Quote::FRONTEND_INTERNAL_STATUSES);
    }
}
