<?php

namespace Oro\Bundle\ApplicationBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;

/**
 * TODO: After adding multiple application in platform this class will be merged with InstallerBundle listener
 */
class RequestListener
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * Installed flag
     *
     * @var bool
     */
    protected $installed;

    /**
     * Debug flag
     *
     * @var bool
     */
    protected $debug;

    /**
     * Application host for install
     *
     * @var string
     */
    protected $installHost;

    /**
     * @param Router $router
     * @param boolean $installed
     * @param bool $debug
     * @param string $installHost
     */
    public function __construct(Router $router, $installed, $installHost, $debug = false)
    {
        $this->router      = $router;
        $this->installed   = $installed;
        $this->debug       = $debug;
        $this->installHost = $installHost;
    }

    public function onRequest(GetResponseEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }

        if (!$this->installed) {
            $allowedRoutes = array(
                'oro_installer_flow',
                'sylius_flow_display',
                'sylius_flow_forward',
            );

            if ($this->debug) {
                $allowedRoutes = array_merge(
                    $allowedRoutes,
                    array(
                        '_wdt',
                        '_profiler',
                        '_profiler_search',
                        '_profiler_search_bar',
                        '_profiler_search_results',
                        '_profiler_router',
                    )
                );
            }

            if (!in_array($event->getRequest()->get('_route'), $allowedRoutes)) {
                $event->setResponse(new RedirectResponse($this->installHost));
            }

            $event->stopPropagation();
        } else {
            // allow open the installer even if the application is already installed
            // this is required because we are clearing the cache on the last installation step
            // and as the result the login page is appeared instead of the final installer page
            if ($event->getRequest()->attributes->get('scenarioAlias') === 'oro_installer' &&
                (
                    $event->getRequest()->attributes->get('_route') === 'sylius_flow_forward' ||
                    $event->getRequest()->attributes->get('_route') === 'sylius_flow_display'
                )
            ) {
                $event->stopPropagation();
            }
        }
    }
}
