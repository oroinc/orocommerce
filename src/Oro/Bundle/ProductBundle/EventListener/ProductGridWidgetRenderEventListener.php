<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;
use Oro\Bundle\ProductBundle\Event\ProductGridWidgetRenderEvent;

class ProductGridWidgetRenderEventListener
{
    /**
     * @var RequestParameterBagFactory
     */
    protected $requestParameterBagFactory;

    public function __construct(RequestParameterBagFactory $requestParameterBagFactory)
    {
        $this->requestParameterBagFactory = $requestParameterBagFactory;
    }

    public function onWidgetRender(ProductGridWidgetRenderEvent $event)
    {
        $params = $event->getWidgetRouteParameters();

        $gridParameters = $this->requestParameterBagFactory->createParameters();

        $event->setWidgetRouteParameters(
            array_merge($params, [RequestParameterBagFactory::DEFAULT_ROOT_PARAM => $gridParameters->all()])
        );
    }
}
