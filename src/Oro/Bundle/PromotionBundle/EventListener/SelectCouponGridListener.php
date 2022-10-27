<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\PromotionBundle\Model\CouponApplicabilityQueryBuilderModifier;

/**
 * Listener modifies the query builder by applicability modifier.
 * @see \Oro\Bundle\PromotionBundle\Model\CouponApplicabilityQueryBuilderModifier.
 */
class SelectCouponGridListener
{
    /**
     * @var CouponApplicabilityQueryBuilderModifier
     */
    private $modifier;

    public function __construct(CouponApplicabilityQueryBuilderModifier $modifier)
    {
        $this->modifier = $modifier;
    }

    public function onBuildAfter(BuildAfter $event)
    {
        $dataSource = $event->getDatagrid()->getDatasource();
        if (!$dataSource instanceof OrmDatasource) {
            return;
        }

        $dataGridQueryBuilder = $dataSource->getQueryBuilder();
        $this->modifier->modify($dataGridQueryBuilder);
    }
}
