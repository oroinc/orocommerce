<?php

namespace OroB2B\Bundle\FrontendBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class FrontendHelper
{
    /**
     * @var string
     */
    protected $backendPrefix;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param string $backendPrefix
     * @param RequestStack $requestStack
     */
    public function __construct($backendPrefix, RequestStack $requestStack)
    {
        $this->backendPrefix = $backendPrefix;
        $this->requestStack = $requestStack;
    }

    /**
     * @param Request|null $request
     * @return bool
     */
    public function isFrontendRequest(Request $request = null)
    {
        $request = $request ?: $this->requestStack->getCurrentRequest();
        if (!$request) {
            // no request means CLI i.e. not frontend
            return false;
        }

        // the least time consuming method to check whether URL is frontend
        return strpos($request->getPathInfo(), $this->backendPrefix) !== 0;
    }
}
