<?php

namespace OroB2B\Bundle\RedirectBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouteCompiler;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;

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
    protected $deniedUrlPatterns = [];

    /**
     * @param Router $router
     * @param ManagerRegistry $registry
     * @param FrontendHelper $frontendHelper
     * @param boolean $installed
     * @param boolean $environment
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

        if ($this->isDeniedUrl($request)) {
            return;
        }

        $this->forwardRequest($request);
    }

    /**
     * @param string $deniedUrlPattern
     * @param bool $env
     */
    public function addDeniedUrlPatterns($deniedUrlPattern, $env)
    {
        $this->deniedUrlPatterns[$env][] = $deniedUrlPattern;
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function isDeniedUrl(Request $request)
    {
        if (!array_key_exists($this->environment, $this->deniedUrlPatterns)) {
            return false;
        }
        $url = $request->getPathInfo();
        foreach ($this->deniedUrlPatterns[$this->environment] as $pattern) {
            if (strpos($pattern, $url)) {
                return true;
            }
        }

        if (!$this->frontendHelper->isFrontendRequest($request)) {
            return true;
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
        $em = $this->registry->getManagerForClass('OroB2BRedirectBundle:Slug');
        $slug = $em->getRepository('OroB2BRedirectBundle:Slug')->findOneBy(['url' => $slugUrl]);

        if ($slug) {
            $routeName = $slug->getRouteName();
            $controller = $this->router->getRouteCollection()->get($routeName)->getDefault('_controller');

            $parameters = [];
            $parameters['_route'] = $routeName;
            $parameters['_controller'] = $controller;

            $redirectRouteParameters = $slug->getRouteParameters();
            $parameters = array_merge($parameters, $redirectRouteParameters);
            $parameters['_route_params'] = $redirectRouteParameters;

            $request->attributes->add($parameters);
        }
    }
}
