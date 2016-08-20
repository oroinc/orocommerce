<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;

class ForwardListener
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var bool
     */
    protected $installed;

    /**
     * @var bool
     */
    protected $environment;

    /**
     * @var array
     */
    protected $skippedUrlPatterns = [];

    /**
     * @param Router $router
     * @param ManagerRegistry $registry
     * @param FrontendHelper $frontendHelper
     * @param boolean $installed
     * @param string $environment
     */
    public function __construct(
        Router $router,
        ManagerRegistry $registry,
        FrontendHelper $frontendHelper,
        $installed,
        $environment
    ) {
        $this->router = $router;
        $this->registry = $registry;
        $this->installed = $installed;
        $this->frontendHelper = $frontendHelper;
        $this->environment = $environment;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->installed) {
            return;
        }

        $request = $event->getRequest();

        if ($request->attributes->has('_controller')
            || $event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST
        ) {
            return;
        }

        if ($this->isSkippedUrl($request)) {
            return;
        }

        $this->forwardRequest($request);
    }

    /**
     * Skipped url pattern should start with slash.
     *
     * @param string $skippedUrlPattern
     * @param string $env
     */
    public function addSkippedUrlPattern($skippedUrlPattern, $env = 'prod')
    {
        $this->skippedUrlPatterns[$env][] = $skippedUrlPattern;
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function isSkippedUrl(Request $request)
    {
        if (!$this->frontendHelper->isFrontendRequest($request)) {
            return true;
        }

        if (array_key_exists($this->environment, $this->skippedUrlPatterns)) {
            $url = $request->getPathInfo();
            foreach ($this->skippedUrlPatterns[$this->environment] as $pattern) {
                if (strpos($url, $pattern) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Request $request
     */
    protected function forwardRequest(Request $request)
    {
        $slugUrl = $request->getPathInfo();
        if ($slugUrl !== '/') {
            $slugUrl = rtrim($slugUrl, '/');
        }

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroRedirectBundle:Slug');
        $slug = $em->getRepository('OroRedirectBundle:Slug')->findOneBy(['url' => $slugUrl]);
        if (!$slug) {
            return;
        }

        $routeName = $slug->getRouteName();
        $routeParameters = $slug->getRouteParameters();

        $generator = $this->router->getGenerator();
        $matcher = $this->router->getMatcher();
        $route = $matcher->match($generator->generate($routeName, $routeParameters));
        if (!array_key_exists('_controller', $route)) {
            return;
        }

        $parameters = [];
        $parameters['_route'] = $routeName;
        $parameters['_controller'] = $route['_controller'];
        $parameters = array_merge($parameters, $routeParameters);
        $parameters['_route_params'] = $routeParameters;

        $request->attributes->add($parameters);
    }
}
