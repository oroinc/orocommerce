<?php

namespace OroB2B\Bundle\FrontendBundle\Menu;

use Symfony\Component\Routing\Route;

use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManager as BaseBreadcrumbManager;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;

class BreadcrumbManager extends BaseBreadcrumbManager implements BreadcrumbManagerInterface
{
    const FRONTEND_MENU = 'frontend_menu';
    const FRONTEND_OPTION = 'frontend';

    /**
     * @param Route|string $route
     * @return bool
     */
    public function supports($route = null)
    {
        if (is_string($route)) {
            $route = $this->router->getRouteCollection()->get($route);
        }

        return $this->isRouteFrontend($route);
    }

    /** {@inheritdoc} */
    public function getBreadcrumbLabels($menu, $routeName)
    {
        $route = $this->router->getRouteCollection()->get($routeName);
        if ($this->isRouteFrontend($route)) {
            $menu = self::FRONTEND_MENU;
        }

        return parent::getBreadcrumbLabels($menu, $routeName);
    }

    /**
     * @param Route $route
     * @return bool
     */
    protected function isRouteFrontend(Route $route)
    {
        return $route->hasOption(self::FRONTEND_OPTION) && true === $route->getOption(self::FRONTEND_OPTION);
    }
}
