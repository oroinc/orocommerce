<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

/**
 * Datagrid listener that converts parameter value from Product Collection grids to array.
 */
class ProductCollectionDatagridParametersListener
{
    /**
     * @var string
     */
    private $parameterName = '';

    /**
     * @param string $parameterName
     */
    public function setParameterName($parameterName)
    {
        $this->parameterName = $parameterName;
    }

    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();

        $datasource = $datagrid->getDatasource();
        if (!$datasource instanceof OrmDatasource) {
            return;
        }

        $parameter = $datasource->getQueryBuilder()->getParameter($this->parameterName);
        if (!$parameter) {
            return;
        }

        $parameterValue = $parameter->getValue();
        if (is_array($parameterValue)) {
            return;
        }

        $newParameterValue = array_map('intval', explode(',', $parameterValue));
        $parameter->setValue($newParameterValue);
    }
}
