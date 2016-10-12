<?php

namespace Oro\Component\WebCatalog;

class RouteData
{
    /**
     * @var string
     */
    protected $route;

    /**
     * @var array|null
     */
    protected $routeParameters;

    /**
     * @param string $route
     * @param array|null $routeParameters
     */
    public function __construct($route, array $routeParameters = null)
    {
        $this->route = $route;
        $this->routeParameters = $routeParameters;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return array|null
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
    }
}
