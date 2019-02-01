<?php

namespace Oro\Bundle\ShippingBundle\Tools;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Symfony\Component\Routing\RouterInterface;

class FilteredDatagridRouteHelper implements DatagridAwareRouteHelperInterface
{
    /**
     * @var string
     */
    protected $gridRouteName;

    /**
     * @var string
     */
    protected $gridName;

    /**
     * @var DatagridRouteHelper
     */
    protected $datagridRouteHelper;

    /**
     * @param string              $gridRouteName
     * @param string              $gridName
     * @param DatagridRouteHelper $datagridRouteHelper
     */
    public function __construct($gridRouteName, $gridName, DatagridRouteHelper $datagridRouteHelper)
    {
        $this->gridRouteName = $gridRouteName;
        $this->gridName = $gridName;
        $this->datagridRouteHelper = $datagridRouteHelper;
    }

    /**
     * Generates URL or URI for the Datagrid filtered by parameters
     *
     * Param 'filters' uses next format ['filterName' => 'filterCriterion', ... , 'filterNameN' => 'filterCriterionN']
     *
     * @param array $filters
     * @param int   $referenceType
     *
     * @return string
     */
    public function generate(array $filters = [], $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        foreach ($filters as $filterName => $filterCriteria) {
            $params['f'][$filterName]['value'][''] = (string)$filterCriteria;
        }

        return $this->datagridRouteHelper->generate(
            $this->gridRouteName,
            $this->gridName,
            $params,
            $referenceType
        );
    }
}
