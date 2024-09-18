<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Provider\HomePageProviderInterface;

/**
 * Provides the set Homepage from the System Configuration by path System Configuration / Websites / Routing.
 */
class HomePageProvider implements HomePageProviderInterface
{
    public function __construct(
        private ConfigManager $configManager,
        private ManagerRegistry $doctrine
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getHomePage(): object
    {
        return $this->getPage() ?? $this->getNotFoundPage();
    }

    private function getPage(): ?Page
    {
        $homePageId = $this->configManager->get(Configuration::getConfigKeyByName(Configuration::HOME_PAGE));
        if (!$homePageId) {
            return null;
        }

        return $this->doctrine->getManagerForClass(Page::class)->find(Page::class, $homePageId);
    }

    private function getNotFoundPage(): Page
    {
        $page = new Page();
        $page->setContent('<h2 align="center">No homepage has been set in the system configuration.</h2>');

        return $page;
    }
}
