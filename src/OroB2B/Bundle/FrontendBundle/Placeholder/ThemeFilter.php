<?php

namespace OroB2B\Bundle\FrontendBundle\Placeholder;

use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

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
     * @param string $theme
     * @return bool
     */
    public function isActiveTheme($theme)
    {
        $activeTheme = $this->themeRegistry->getActiveTheme();
        return $activeTheme ? $activeTheme->getName() === $theme : false;
    }
}
