<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class WebCatalogProvider
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var EntityRepository
     */
    protected $webCatalogRepository;

    /**
     * @param ConfigManager $config
     * @param EntityRepository $webCatalogRepository
     */
    public function __construct(ConfigManager $config, EntityRepository $webCatalogRepository)
    {
        $this->configManager = $config;
        $this->webCatalogRepository = $webCatalogRepository;
    }

    /**
     * @param int|null $websiteId
     *
     * @return WebCatalog|null
     */
    public function getWebCatalog($websiteId = null)
    {
        $webCatalogId = $this->configManager->get('oro_web_catalog.web_catalog');
        
        if (is_null($webCatalogId)) {
            return null;
        }
        
        $webCatalog = $this->webCatalogRepository
            ->findOneById($webCatalogId);
        
        return $webCatalog;
    }
}
