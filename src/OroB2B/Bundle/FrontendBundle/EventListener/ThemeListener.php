<?php

namespace OroB2B\Bundle\FrontendBundle\EventListener;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

class ThemeListener
{
    const FRONTEND_THEME = 'demo';

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var ThemeRegistry
     */
    protected $themeRegistry;

    /**
     * @var bool
     */
    protected $installed;

    /**
     * @param RouterInterface $router
     * @param ThemeRegistry $themeRegistry
     * @param boolean $installed
     */
    public function __construct(RouterInterface $router, ThemeRegistry $themeRegistry, $installed)
    {
        $this->router = $router;
        $this->themeRegistry = $themeRegistry;
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

        if ($route->getOption(RouteCollectionListener::OPTION_FRONTEND)) {
            $this->themeRegistry->setActiveTheme(self::FRONTEND_THEME);
        }
    }
}
