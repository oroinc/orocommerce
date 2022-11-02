<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Emulates a storefront request during previewing CMS widgets in the backoffice.
 */
class FrontendEmulator
{
    private WebsiteManager $websiteManager;
    private CurrentLocalizationProvider $currentLocalizationProvider;
    private UserLocalizationManagerInterface $userLocalizationManager;

    public function __construct(
        WebsiteManager $websiteManager,
        CurrentLocalizationProvider $currentLocalizationProvider,
        UserLocalizationManagerInterface $userLocalizationManager
    ) {
        $this->websiteManager = $websiteManager;
        $this->currentLocalizationProvider = $currentLocalizationProvider;
        $this->userLocalizationManager = $userLocalizationManager;
    }

    public function startFrontendRequestEmulation(): void
    {
        $this->websiteManager->setCurrentWebsite(
            $this->websiteManager->getDefaultWebsite()
        );
        $this->currentLocalizationProvider->setCurrentLocalization(
            $this->userLocalizationManager->getDefaultLocalization()
        );
    }

    public function stopFrontendRequestEmulation(): void
    {
        $this->websiteManager->setCurrentWebsite(null);
        $this->currentLocalizationProvider->setCurrentLocalization(null);
    }
}
