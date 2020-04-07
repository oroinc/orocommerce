<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\DependencyInjection\OroRedirectExtension;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Component\Routing\RouteData;

/**
 * Layout data provider. Returns category canonical URL with respect to Include Subcategories parameter.
 */
class CategoryCanonicalUrlDataProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var WebsiteUrlResolver */
    protected $websiteSystemUrlResolver;

    /**
     * @param ConfigManager $configManager
     * @param WebsiteUrlResolver $websiteSystemUrlResolver
     */
    public function __construct(
        ConfigManager $configManager,
        WebsiteUrlResolver $websiteSystemUrlResolver
    ) {
        $this->configManager = $configManager;
        $this->websiteSystemUrlResolver = $websiteSystemUrlResolver;
    }

    /**
     * @param Category $category
     * @param bool $includeSubcategories
     * @return string
     */
    public function getUrl(Category $category, bool $includeSubcategories = false)
    {
        $routeData = $this->getRouteData($category, $includeSubcategories);

        if ($this->getCanonicalUrlSecurityType() === Configuration::SECURE) {
            $url = $this->websiteSystemUrlResolver->getWebsiteSecurePath(
                $routeData->getRoute(),
                $routeData->getRouteParameters()
            );
        } else {
            $url = $this->websiteSystemUrlResolver->getWebsitePath(
                $routeData->getRoute(),
                $routeData->getRouteParameters()
            );
        }

        return $url;
    }

    /**
     * @param Category $category
     * @param bool $includeSubcategories
     * @return RouteData
     */
    private function getRouteData(Category $category, bool $includeSubcategories)
    {
        return new RouteData(
            'oro_product_frontend_product_index',
            [
                'categoryId' => $category->getId(),
                'includeSubcategories' => $includeSubcategories
            ]
        );
    }

    /**
     * @return string
     */
    private function getCanonicalUrlSecurityType()
    {
        $configKey = $this->getConfigKey(Configuration::CANONICAL_URL_SECURITY_TYPE);

        return $this->configManager->get($configKey);
    }

    /**
     * @param string $configField
     * @return string
     */
    private function getConfigKey($configField)
    {
        return sprintf('%s.%s', OroRedirectExtension::ALIAS, $configField);
    }
}
