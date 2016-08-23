<?php

namespace Oro\Bundle\FrontendBundle\Request;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class FrontendHelper
{
    /**
     * @var string
     */
    protected $backendPrefix;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param string $backendPrefix
     * @param ContainerInterface $container
     */
    public function __construct($backendPrefix, ContainerInterface $container)
    {
        $this->backendPrefix = $backendPrefix;
        $this->container = $container;
    }

    /**
     * @param Request|null $request
     * @return bool
     */
    public function isFrontendRequest(Request $request = null)
    {
        $request = $request ?: $this->container->get('request_stack')->getCurrentRequest();
        if (!$request || !$this->container->getParameter('installed')) {
            // no request means CLI i.e. not frontend
            return false;
        }

        // the least time consuming method to check whether URL is frontend
        return strpos($request->getPathInfo(), $this->backendPrefix) !== 0;
    }
}
