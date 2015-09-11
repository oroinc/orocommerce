<?php

namespace OroB2B\Bundle\FrontendBundle\Placeholder;

use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

use OroB2B\Bundle\FrontendBundle\EventListener\ThemeListener;

class ThemeFilter
{
    /**
     * @var ThemeRegistry
     */
    protected $themeRegistry;

    /**
     * @param ThemeRegistry $themeRegistry
     */
    public function __construct(ThemeRegistry $themeRegistry)
    {
        $this->themeRegistry = $themeRegistry;
    }

    /**
     * @return bool
     */
    public function isDemoTheme()
    {
        $activeTheme = $this->themeRegistry->getActiveTheme();
        return $activeTheme ? $activeTheme->getName() === ThemeListener::FRONTEND_THEME : false;
    }

    /**
     * @return bool
     */
    public function isDefaultTheme()
    {
        return !$this->isDemoTheme();
    }
}
