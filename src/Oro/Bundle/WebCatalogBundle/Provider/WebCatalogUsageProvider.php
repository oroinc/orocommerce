<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
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
     * @param ConfigManager     $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
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
        return [
            0 => (int)$this->configManager->get(static::SETTINGS_KEY)
        ];
    }
}
