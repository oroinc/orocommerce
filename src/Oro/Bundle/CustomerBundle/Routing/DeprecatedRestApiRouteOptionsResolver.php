<?php

namespace Oro\Bundle\CustomerBundle\Routing;

use Symfony\Component\Routing\Route;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;

/**
 * As Commerce application is not released yet and it does not need BC
 * the deprecated REST API routes can be removed.
 * This class should be removed after all "*_deprecated" routes were removed
 * from Oro/Bundle/ApiBundle/Resources/config/oro/routing.yml
 */
class DeprecatedRestApiRouteOptionsResolver implements RouteOptionsResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        $group = $route->getOption('group');
        if ($group === 'rest_api_deprecated') {
            $routes->remove($routes->getName($route));
        }
    }
}
