<?php

namespace OroB2B\Bundle\FrontendBundle\Request;

use Symfony\Component\HttpFoundation\Request;

class FrontendHelper
{
    /**
     * @var string
     */
    protected $backendPrefix;

    /**
     * @param string $backendPrefix
     */
    public function __construct($backendPrefix)
    {
        $this->backendPrefix = $backendPrefix;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isFrontendRequest(Request $request)
    {
        // the least time consuming method to check whether URL is frontend
        return strpos($request->getPathInfo(), $this->backendPrefix) !== 0;
    }
}
