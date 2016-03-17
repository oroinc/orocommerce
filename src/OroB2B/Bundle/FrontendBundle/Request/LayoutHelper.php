<?php

namespace OroB2B\Bundle\FrontendBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LayoutHelper
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param Request|null $request
     * @return bool
     */
    public function isLayoutRequest(Request $request = null)
    {
        $request = $request ?: $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new BadRequestHttpException('Request is not defined');
        }

        return $request->attributes->has('_layout');
    }
}
