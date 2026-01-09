<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;
use Oro\Bundle\ProductBundle\Event\ProductGridWidgetRenderEvent;

/**
 * Enriches product grid widget route parameters with datagrid request parameters.
 *
 * This listener handles {@see ProductGridWidgetRenderEvent} events and merges the current datagrid parameters
 * into the widget route parameters, ensuring proper state preservation when rendering product grid widgets.
 */
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
