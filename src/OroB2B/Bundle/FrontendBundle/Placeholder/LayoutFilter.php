<?php

namespace OroB2B\Bundle\FrontendBundle\Placeholder;

use OroB2B\Bundle\FrontendBundle\Request\LayoutHelper;

class LayoutFilter
{
    /**
     * @var LayoutHelper
     */
    protected $helper;

    /**
     * @param LayoutHelper $helper
     */
    public function __construct(LayoutHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @return bool
     */
    public function isLayoutRoute()
    {
        return $this->helper->isLayoutRequest();
    }

    /**
     * @return bool
     */
    public function isSPARoute()
    {
        return !$this->helper->isLayoutRequest();
    }
}
