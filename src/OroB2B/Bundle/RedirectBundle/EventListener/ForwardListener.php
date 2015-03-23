<?php

namespace OroB2B\Bundle\RedirectBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
     * @param Router $router
     * @param ManagerRegistry $registry
     */
    public function __construct(Router $router, ManagerRegistry $registry)
    {
        $this->router = $router;
        $this->registry = $registry;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (
            $request->attributes->has('_controller')
            || $event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST
        ) {
            return;
        }

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
