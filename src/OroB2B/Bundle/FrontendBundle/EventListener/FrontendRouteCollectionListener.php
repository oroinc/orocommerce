<?php

namespace OroB2B\Bundle\FrontendBundle\EventListener;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;

class FrontendRouteCollectionListener
{
    /**
     * @var array
     */
    protected $routeNames;

    /**
     * @param array $routeNames
     */
    public function __construct(array $routeNames = [])
    {
        $this->routeNames = $routeNames;
    }

    /**
     * @param RouteCollectionEvent $event
     */
    public function onCollectionAutoload(RouteCollectionEvent $event)
    {
        if (0 === count($this->routeNames)) {
            return;
        }

        $collection = $event->getCollection();
        foreach ($this->routeNames as $routeName) {
            $route = $collection->get($routeName);
            if ($route) {
                $route->setOption(RouteCollectionListener::OPTION_FRONTEND, true);
            }
        }
    }
}
