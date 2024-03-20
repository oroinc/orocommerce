<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Provides the set Homepage from the System Configuration by path System Configuration / Websites / Routing.
 */
class HomeLandingPageProvider
{
    public function __construct(
        private ConfigManager $configManager,
        private ManagerRegistry $doctrine,
    ) {
    }

    public function getHomeLandingPage(): Page
    {
        $homePageId = $this->configManager->get(Configuration::getConfigKeyByName(Configuration::HOME_PAGE));
        if ($homePageId) {
            $homePage = $this->doctrine
                ->getRepository(Page::class)
                ->find($homePageId);
        }

        return $homePage ?? $this->getNotFoundPage();
    }

    public function getNotFoundPage(): Page
    {
        return (new Page())->setContent(
            '<h2 align="center">No homepage has been set in the system configuration.</h2>'
        );
    }
}
