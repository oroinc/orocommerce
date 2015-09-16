<?php

namespace OroB2B\Bundle\FrontendBundle\Placeholder;

use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;

class FrontendFilter
{
    /**
     * @var FrontendHelper
     */
    protected $helper;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param FrontendHelper $helper
     */
    public function __construct(FrontendHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param Request|null $request
     */
    public function setRequest(Request $request = null)
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

        return $this->helper->isFrontendRequest($this->request);
    }

    /**
     * @return bool
     */
    public function isBackendRoute()
    {
        if (!$this->request) {
            return true;
        }

        return !$this->helper->isFrontendRequest($this->request);
    }
}
