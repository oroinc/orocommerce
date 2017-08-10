<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\PromotionBundle\Model\CouponApplicabilityQueryBuilderModifier;

class SelectCouponGridListener
{
    /**
     * @var CouponApplicabilityQueryBuilderModifier
     */
    private $modifier;

    /**
     * @param CouponApplicabilityQueryBuilderModifier $modifier
     */
    public function __construct(CouponApplicabilityQueryBuilderModifier $modifier)
    {
        $this->modifier = $modifier;
    }

    /**
     * @param BuildAfter $event
     */
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
