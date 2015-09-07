<?php

namespace OroB2B\Bundle\FrontendBundle\EventListener;

use Symfony\Component\Routing\Route;

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
    public function __construct($routeNames = [])
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

        /** @var Route $route */
        foreach ($event->getCollection()->getIterator() as $name => $route) {
            if (in_array($name, $this->routeNames, true)) {
                $route->setOption(RouteCollectionListener::OPTION_FRONTEND, true);
            }
        }
    }
}
