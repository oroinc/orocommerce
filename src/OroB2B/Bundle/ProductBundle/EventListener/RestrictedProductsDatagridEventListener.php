<?php

namespace Oro\Bundle\ProductBundle\EventListener;

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
     * @param RequestStack $requestStack
     * @param ProductManager $productManager
     */
    public function __construct(
        RequestStack $requestStack,
        ProductManager $productManager
    ) {
        $this->requestStack = $requestStack;
        $this->productManager = $productManager;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $dataSource */
        $dataSource = $event->getDatagrid()->getDatasource();
        $queryBuilder = $dataSource->getQueryBuilder();
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !$params = $request->get(ProductSelectType::DATA_PARAMETERS)) {
            $params = [];
        }
        $this->productManager->restrictQueryBuilder($queryBuilder, $params);
    }
}
