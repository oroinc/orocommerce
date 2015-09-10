<?php

namespace OroB2B\Bundle\FrontendBundle\Placeholder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

use OroB2B\Bundle\FrontendBundle\EventListener\RouteCollectionListener;

class FrontendFilter
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param Request|null $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return bool
     */
    public function isFrontendRoute()
    {
        if (!$this->request) {
            return false;
        }

        return $this->isFrontendRequest($this->request);
    }

    /**
     * @return bool
     */
    public function isBackendRoute()
    {
        if (!$this->request) {
            return true;
        }

        return !$this->isFrontendRequest($this->request);
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function isFrontendRequest(Request $request)
    {
        $routeName = $request->attributes->get('_route');
        if (!$routeName) {
            return false;
        }

        $route = $this->router->getRouteCollection()->get($routeName);
        if (!$route) {
            return false;
        }

        return $route->getOption(RouteCollectionListener::OPTION_FRONTEND) === true;
    }
}
