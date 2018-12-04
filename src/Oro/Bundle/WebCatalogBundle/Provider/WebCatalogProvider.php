<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Website\WebsiteInterface;

/**
 * This provider returns current web catalog and current navigation root, which were indicated in system configuration
 */
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

    /**
     * @param WebsiteInterface|null $website
     * @return null|ContentNode
     */
    public function getNavigationRoot(WebsiteInterface $website = null)
    {
        $webCatalogId = (int) $this->configManager->get('oro_web_catalog.web_catalog', false, false, $website);
        $contentNodeId = $this->configManager->get('oro_web_catalog.navigation_root', false, false, $website);

        if ($contentNodeId) {
            /** @var ContentNode $contentNode */
            $contentNode = $this->registry->getManagerForClass(ContentNode::class)
                ->find(ContentNode::class, $contentNodeId);

            if ($contentNode && $contentNode->getWebCatalog()->getId() === $webCatalogId) {
                return $contentNode;
            }
        }

        return null;
    }
}
