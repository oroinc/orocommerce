<?php

namespace Oro\Bundle\ApplicationBundle\EventListener;

use Symfony\Component\Routing\Route;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;

class RouteCollectionListener
{
    const OPTION_FRONTEND = 'frontend';

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @param string $prefix
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @param RouteCollectionEvent $event
     */
    public function onCollectionAutoload(RouteCollectionEvent $event)
    {
        $prefix = trim(trim($this->prefix), '/');
        if ('' === $prefix) {
            return;
        }

        /** @var Route $route */
        foreach ($event->getCollection()->getIterator() as $route) {
            $path = $route->getPath();
            if (false !== strpos($path, $prefix)) {
                continue;
            }

            if ($route->hasOption(self::OPTION_FRONTEND) && $route->getOption(self::OPTION_FRONTEND)) {
                continue;
            }

            $route->setPath($prefix . $route->getPath());
        }
    }
}
