<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Website\WebsiteInterface;

class WebCatalogScopeCriteriaProvider
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(
        ConfigManager $configManager,
        ScopeManager $scopeManager,
        ManagerRegistry $registry
    ) {
        $this->configManager = $configManager;
        $this->scopeManager = $scopeManager;
        $this->registry = $registry;
    }

    /**
     * @param WebsiteInterface|null $website
     * @return ScopeCriteria
     */
    public function getWebCatalogScopeForAnonymousCustomerGroup(WebsiteInterface $website = null)
    {
        return $this->scopeManager->getCriteria(
            'web_content',
            [
                'website' => $website,
                'webCatalog' => $this->getWebCatalog($website),
                'customerGroup' => $this->getAnonymousUserGroup($website)
            ]
        );
    }

    /**
     * @param WebsiteInterface|null $website
     * @return null|WebCatalog
     */
    private function getWebCatalog(WebsiteInterface $website = null)
    {
        $webCatalogId = $this->configManager->get('oro_web_catalog.web_catalog', false, false, $website);
        $webCatalog = null;
        if ($webCatalogId) {
            $webCatalog = $this->registry
                ->getManagerForClass(WebCatalog::class)
                ->find(WebCatalog::class, $webCatalogId);
        }

        return $webCatalog;
    }

    /**
     * @param WebsiteInterface|null $website
     * @return null|CustomerGroup
     */
    private function getAnonymousUserGroup(WebsiteInterface $website = null)
    {
        $anonymousGroupId = $this->configManager->get('oro_customer.anonymous_customer_group', false, false, $website);
        $anonymousGroup = null;
        if ($anonymousGroupId) {
            $anonymousGroup = $this->registry
                ->getManagerForClass(CustomerGroup::class)
                ->find(CustomerGroup::class, $anonymousGroupId);
        }

        return $anonymousGroup;
    }
}
