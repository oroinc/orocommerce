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
        if (!$request) {
            $request = $this->container->get('request_stack')->getCurrentRequest();
        }

        return $request && $this->isFrontendUrl($request->getPathInfo());
    }

    /**
     * @param string $url
     * @return bool
     */
    public function isFrontendUrl($url)
    {
        // the least time consuming method to check whether URL is frontend
        return $this->container->getParameter('installed') && strpos($url, $this->backendPrefix) !== 0;
    }
}
