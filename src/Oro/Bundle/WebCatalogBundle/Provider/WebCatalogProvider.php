<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Website\WebsiteInterface;

class WebCatalogProvider
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ManagerRegistry $registry
     * @param ConfigManager $configManager
     */
    public function __construct(ManagerRegistry $registry, ConfigManager $configManager)
    {
        $this->registry = $registry;
        $this->configManager = $configManager;
    }

    /**
     * @param WebsiteInterface|null $website
     * @return null|WebCatalog
     */
    public function getWebCatalog(WebsiteInterface $website = null)
    {
        $webCatalogId = $this->configManager->get('oro_web_catalog.web_catalog', false, false, $website);

        if ($webCatalogId) {
            return $this->registry->getManagerForClass(WebCatalog::class)
                ->find(WebCatalog::class, $webCatalogId);
        }

        return null;
    }
}
