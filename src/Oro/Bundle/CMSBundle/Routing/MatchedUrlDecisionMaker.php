<?php

namespace Oro\Bundle\CMSBundle\Routing;

use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RedirectBundle\Provider\SlugEntityFinder;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker as BaseMatchedUrlDecisionMaker;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

/**
 * BC layer for using old-style homepage.
 * Checks if URL is storefront URL, and it is not configured to be skipped on storefront.
 */
class MatchedUrlDecisionMaker extends BaseMatchedUrlDecisionMaker
{
    private ThemeConfigurationProvider $themeConfigurationProvider;

    private ThemeManager $themeManager;

    private SlugEntityFinder $slugEntityFinder;

    private ConfigManager $configManager;

    private ApplicationState $applicationState;

    public function __construct(
        array $skippedUrlPatterns,
        FrontendHelper $frontendHelper,
        ThemeConfigurationProvider $themeConfigurationProvider,
        ThemeManager $themeManager,
        SlugEntityFinder $slugEntityFinder,
        ConfigManager $configManager,
        ApplicationState $applicationState
    ) {
        parent::__construct($skippedUrlPatterns, $frontendHelper);

        $this->themeConfigurationProvider = $themeConfigurationProvider;
        $this->themeManager = $themeManager;
        $this->slugEntityFinder = $slugEntityFinder;
        $this->configManager = $configManager;
        $this->applicationState = $applicationState;
    }

    #[\Override]
    public function matches(string $pathinfo): bool
    {
        if (!$this->applicationState->isInstalled()) {
            return parent::matches($pathinfo);
        }

        if ($pathinfo !== '/') {
            return parent::matches($pathinfo);
        }

        $currentTheme = $this->themeConfigurationProvider->getThemeName();
        if ($currentTheme === null) {
            return parent::matches($pathinfo);
        }

        if (!$this->themeManager->themeHasParent($currentTheme, ['default_50', 'default_51'])) {
            return parent::matches($pathinfo);
        }

        $slug = $this->slugEntityFinder->findSlugEntityByUrl($pathinfo);
        if (!$slug || $slug->getRouteName() !== 'oro_cms_frontend_page_view') {
            return parent::matches($pathinfo);
        }

        $homePageId = $this->configManager->get(Configuration::getConfigKeyByName(Configuration::HOME_PAGE));
        if ($homePageId === null) {
            return parent::matches($pathinfo);
        }

        $routeIdParameter = $slug->getRouteParameters()['id'] ?? null;
        if ($routeIdParameter === $homePageId) {
            // Skips homepage slug for root content node.
            // Route "oro_frontend_root" will be used.
            return false;
        }

        return parent::matches($pathinfo);
    }
}
