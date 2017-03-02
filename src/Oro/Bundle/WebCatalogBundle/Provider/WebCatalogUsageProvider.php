<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;

class WebCatalogUsageProvider implements WebCatalogUsageProviderInterface
{
    const SETTINGS_KEY = 'oro_web_catalog.web_catalog';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param ConfigManager   $configManager
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ConfigManager $configManager, ManagerRegistry $managerRegistry)
    {
        $this->configManager = $configManager;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function isInUse(WebCatalogInterface $webCatalog)
    {
        $usedWebCatalogId = (int)$this->configManager->get(static::SETTINGS_KEY);

        return $usedWebCatalogId === $webCatalog->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getAssignedWebCatalogs(array $entities = [])
    {
        $webCatalogId = (int)$this->configManager->get(static::SETTINGS_KEY);

        if (!$webCatalogId) {
            return [];
        }

        return [
            $this->getWebsiteRepository()->getDefaultWebsite()->getId() => $webCatalogId
        ];
    }

    /**
     * @return WebsiteRepository
     */
    protected function getWebsiteRepository()
    {
        /** @var WebsiteRepository $websiteRepository */
        return $this->managerRegistry->getManagerForClass(Website::class)->getRepository(Website::class);
    }
}
