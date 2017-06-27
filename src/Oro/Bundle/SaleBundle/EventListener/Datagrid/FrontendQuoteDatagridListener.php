<?php

namespace Oro\Bundle\SaleBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\SaleBundle\Entity\Quote;

/**
 * Appends Frontend Datagrid query with proper internal statuses.
 */
class FrontendQuoteDatagridListener
{
    /**
     * @param OrmResultBeforeQuery $event
     */
    public function onResultBeforeQuery(OrmResultBeforeQuery $event)
    {
        $qb = $event->getQueryBuilder();

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
