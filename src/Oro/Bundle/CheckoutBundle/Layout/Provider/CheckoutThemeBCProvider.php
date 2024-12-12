<?php

namespace Oro\Bundle\CheckoutBundle\Layout\Provider;

use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

/**
 * Responsible for checking whether the specified theme is new.
 * This is required to maintain BC at the layout level for older themes.
 */
class CheckoutThemeBCProvider
{
    private array $themes = [];

    public function __construct(private CurrentThemeProvider $currentThemeProvider, private ThemeManager $themeManager)
    {
    }

    public function addTheme(string $theme): self
    {
        if (!in_array($theme, $this->themes, true)) {
            $this->themes[] = $theme;
        }

        return $this;
    }

    public function getThemes(): array
    {
        return $this->themes;
    }

    public function isOldTheme(): bool
    {
        $currentThemeId = $this->currentThemeProvider->getCurrentThemeId();

        return $this->themeManager->themeHasParent($currentThemeId, $this->getThemes());
    }
}
