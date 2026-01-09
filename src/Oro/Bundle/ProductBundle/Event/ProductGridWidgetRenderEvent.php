<?php

namespace Oro\Bundle\ProductBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a product grid widget is being rendered.
 *
 * This event allows listeners to modify the route parameters used for rendering the product grid widget,
 * enabling customization of grid behavior and appearance based on context or business requirements.
 */
class ProductGridWidgetRenderEvent extends Event
{
    public const NAME = 'product_grid.render';

    /**
     * @var array
     */
    protected $widgetRouteParameters = [];

    public function __construct(array $widgetRouteParameters)
    {
        $this->widgetRouteParameters = $widgetRouteParameters;
    }

    /**
     * @return array
     */
    public function getWidgetRouteParameters()
    {
        return $this->widgetRouteParameters;
    }

    /**
     * @param array $widgetRouteParameters
     * @return ProductGridWidgetRenderEvent
     */
    public function setWidgetRouteParameters($widgetRouteParameters)
    {
        $this->widgetRouteParameters = $widgetRouteParameters;

        return $this;
    }
}
