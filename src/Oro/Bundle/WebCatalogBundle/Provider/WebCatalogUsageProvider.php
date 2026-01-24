<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;

/**
 * Determines which web catalog is currently in use based on system configuration.
 *
 * This provider checks whether a specific web catalog is assigned as the active catalog in the system configuration.
 * It is used to prevent deletion or modification of web catalogs that are currently being used
 * to render the storefront, and to identify which web catalog is assigned to which website.
 */
class WebCatalogUsageProvider implements WebCatalogUsageProviderInterface
{
    const SETTINGS_KEY = 'oro_web_catalog.web_catalog';

    /** @var ConfigManager */
    private $configManager;

    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(ConfigManager $configManager, ManagerRegistry $doctrine)
    {
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
    }

    #[\Override]
    public function isInUse(WebCatalogInterface $webCatalog)
    {
        return $this->getWebCatalogId() === $webCatalog->getId();
    }

    #[\Override]
    public function getAssignedWebCatalogs()
    {
        $webCatalogId = $this->getWebCatalogId();
        if (!$webCatalogId) {
            return [];
        }

        return [
            $this->getWebsiteRepository()->getDefaultWebsite()->getId() => $webCatalogId
        ];
    }

    /**
     * @return int
     */
    private function getWebCatalogId()
    {
        return (int)$this->configManager->get(self::SETTINGS_KEY);
    }

    /**
     * @return WebsiteRepository
     */
    private function getWebsiteRepository()
    {
        return $this->doctrine->getManagerForClass(Website::class)->getRepository(Website::class);
    }
}
