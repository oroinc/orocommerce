<?php

namespace OroB2B\Bundle\FrontendBundle\EventListener;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;

abstract class AbstractFrontendDatagridListener
{
    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @param FrontendHelper $frontendHelper
     */
    public function __construct(FrontendHelper $frontendHelper)
    {
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * @return bool
     */
    protected function isFrontendRequest()
    {
        return $this->frontendHelper->isFrontendRequest();
    }
}
