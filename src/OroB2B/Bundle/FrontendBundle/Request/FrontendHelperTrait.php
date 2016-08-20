<?php

namespace Oro\Bundle\FrontendBundle\Request;

use Symfony\Component\HttpFoundation\Request;

trait FrontendHelperTrait
{
    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @param Request|null $request
     * @return bool
     */
    protected function isFrontendRequest(Request $request = null)
    {
        return $this->frontendHelper->isFrontendRequest($request);
    }
}
