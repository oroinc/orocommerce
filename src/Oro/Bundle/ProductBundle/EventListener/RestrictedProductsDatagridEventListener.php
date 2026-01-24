<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Applies product restrictions to datagrids based on request context.
 *
 * This listener modifies product datagrids after they are built, applying restrictions
 * based on the current request parameters to ensure that only appropriate products are displayed in grid views
 * throughout the application.
 */
class RestrictedProductsDatagridEventListener
{
    /** @var  RequestStack */
    protected $requestStack;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var  ProductManager */
    protected $productManager;

    public function __construct(
        RequestStack $requestStack,
        ProductManager $productManager
    ) {
        $this->requestStack   = $requestStack;
        $this->productManager = $productManager;
    }

    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $dataSource */
        $dataSource   = $event->getDatagrid()->getDatasource();
        $queryBuilder = $dataSource->getQueryBuilder();
        $request      = $this->requestStack->getCurrentRequest();
        if (!$request || !$params = $request->get(ProductSelectType::DATA_PARAMETERS)) {
            $params = [];
        }
        $this->productManager->restrictQueryBuilder($queryBuilder, $params);
    }
}
