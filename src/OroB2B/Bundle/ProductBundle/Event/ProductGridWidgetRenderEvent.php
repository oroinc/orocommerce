<?php

namespace OroB2B\Bundle\ProductBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ProductGridWidgetRenderEvent extends Event
{
    const NAME = 'product_grid.render';

    /**
     * @var array
     */
    protected $widgetRouteParameters = [];

    /**
     * @param array $widgetRouteParameters
     */
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
