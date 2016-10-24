<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class WebCatalogUsageProvider
{
    const SETTINGS_KEY = 'oro_web_catalog.web_catalog';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param WebCatalog $webCatalog
     * @return bool
     */
    public function isInUse(WebCatalog $webCatalog)
    {
        $usedWebCatalogId = (int)$this->configManager->get(self::SETTINGS_KEY);

        return $usedWebCatalogId === $webCatalog->getId();
    }
}
