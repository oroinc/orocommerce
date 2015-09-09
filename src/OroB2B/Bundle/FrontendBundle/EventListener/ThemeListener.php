<?php

namespace OroB2B\Bundle\FrontendBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

class ThemeListener
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var ThemeRegistry
     */
    protected $oroThemeRegistry;

    /**
     * @var bool
     */
    protected $installed;

    /**
     * @param Router $router
     * @param ThemeRegistry $oroThemeRegistry
     * @param boolean $installed
     */
    public function __construct(Router $router, ThemeRegistry $oroThemeRegistry, $installed)
    {
        $this->router = $router;
        $this->oroThemeRegistry = $oroThemeRegistry;
        $this->installed = $installed;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->installed) {
            return;
        }

        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $request = $event->getRequest();

        $routeName = $request->attributes->get('_route');
        $route = $this->router->getRouteCollection()->get($routeName);

        if ($route->getOption('frontend')) {
            $this->oroThemeRegistry->setActiveTheme('demo');
        }
    }
}
