<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestProvider
{
    /** @var Request */
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return string
     */
    public function getClientIp()
    {
        return $this->requestStack->getMasterRequest()->getClientIp();
    }
}
