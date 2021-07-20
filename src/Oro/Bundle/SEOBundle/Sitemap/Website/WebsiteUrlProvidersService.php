<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Website;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistryInterface;
use Oro\Component\Website\WebsiteInterface;

class WebsiteUrlProvidersService implements WebsiteUrlProvidersServiceInterface
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var UrlItemsProviderRegistryInterface
     */
    private $urlItemsProviderRegistry;

    /**
     * @var UrlItemsProviderRegistryInterface
     */
    private $accessDeniedProviderRegistry;

    public function __construct(
        ConfigManager $configManager,
        UrlItemsProviderRegistryInterface $urlItemsProviderRegistry,
        UrlItemsProviderRegistryInterface $accessDeniedProviderRegistry
    ) {
        $this->configManager = $configManager;
        $this->urlItemsProviderRegistry = $urlItemsProviderRegistry;
        $this->accessDeniedProviderRegistry = $accessDeniedProviderRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function getWebsiteProvidersIndexedByNames(WebsiteInterface $website)
    {
        if (!$this->configManager->get('oro_frontend.guest_access_enabled', false, false, $website)) {
            return $this->accessDeniedProviderRegistry->getProvidersIndexedByNames();
        }
        return $this->urlItemsProviderRegistry->getProvidersIndexedByNames();
    }
}
