<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;

class RestrictedProductsDatagridEventListener
{
    /** @var  RequestStack */
    protected $requestStack;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var  ProductManager */
    protected $productManager;

    /**
     * @param RequestStack   $requestStack
     * @param ProductManager $productManager
     */
    public function __construct(
        RequestStack $requestStack,
        ProductManager $productManager
    ) {
        $this->requestStack   = $requestStack;
        $this->productManager = $productManager;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource|SearchDatasource $dataSource */
        $dataSource = $event->getDatagrid()->getDatasource();
        $request    = $this->requestStack->getCurrentRequest();
        if (!$request || !$params = $request->get(ProductSelectType::DATA_PARAMETERS)) {
            $params = [];
        }

        if (is_a($dataSource, OrmDatasource::class)) {
            $queryBuilder = $dataSource->getQueryBuilder();
            $this->productManager->restrictQueryBuilder($queryBuilder, $params);
        } elseif (is_a($dataSource, SearchDatasource::class)) {
            $websiteQuery = $dataSource->getSearchQuery();
            $this->productManager->restrictSearchQuery($websiteQuery->getQuery());
        }
    }
}
