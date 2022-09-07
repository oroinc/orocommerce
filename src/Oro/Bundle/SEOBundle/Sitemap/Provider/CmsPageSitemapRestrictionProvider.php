<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Component\Website\WebsiteInterface;

/**
 * Provides cms pages' sitemap restriction functionality based on website configurations and features
 *
 * Restriction is active when webcatalog feature is disabled
 *
 * ---------------------------------------------------------------------------------------------------------
 * | Exclude Direct URLs | Include Landing Pages   | Landing Pages Not Used    | Landing Pages Used        |
 * | Of Landing Pages    | Not Used In Web Catalog | In Web Catalog            | In Web Catalog            |
 * ---------------------------------------------------------------------------------------------------------
 * |  ✓                  | ✓                       | Included with direct URLs | Excluded                  |
 * |  ✓                  |                         | Excluded                  | Excluded                  |
 * |                     | ✓                       | Included with direct URLs | Included with direct URLs |
 * |                     |                         | Excluded                  | Included with direct URLs |
 * ---------------------------------------------------------------------------------------------------------
 */
class CmsPageSitemapRestrictionProvider implements SwitchableUrlItemsProviderInterface
{
    private const EXCLUDE_WEB_CATALOG_LANDING_PAGES = 'oro_seo.sitemap_exclude_landing_pages';
    private const INCLUDE_NOT_IN_WEB_CATALOG_LANDING_PAGES = 'oro_seo.sitemap_include_landing_pages_not_in_web_catalog';

    use FeatureCheckerHolderTrait;

    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * If 'Exclude Direct URLs Of Landing Pages' is unchecked and
     * 'Include Landing Pages Not Used In Web Catalog' is unchecked
     * indicates that the sitemap for landing pages will only contain pages that belong to the web catalog.
     *
     * @param WebsiteInterface|null $website
     * @return bool
     */
    public function isRestrictedToPagesBelongToWebCatalogOnly(WebsiteInterface $website = null): bool
    {
        return !$this->isExcludedPagesBelongToWebCatalog($website)
            && !$this->isIncludedPagesNotBelongToWebCatalog($website);
    }

    /**
     * If 'Exclude Direct URLs Of Landing Pages' is checked and
     * 'Include Landing Pages Not Used In Web Catalog' is unchecked
     * A sitemap for landing pages will not be generated.
     *
     * @param WebsiteInterface|null $website
     * @return bool
     */
    public function isUrlItemsExcluded(WebsiteInterface $website = null): bool
    {
        return $this->isExcludedPagesBelongToWebCatalog($website)
            && !$this->isIncludedPagesNotBelongToWebCatalog($website);
    }

    /**
     * Restriction is active when webcatalog feature is disabled
     * and both 'Exclude Direct URLs Of Landing Pages' and 'Include Landing Pages Not Used In Web Catalog'
     * options are checked (the pages do not belong to the web directory)
     * or unchecked (the pages belong to the web directory).
     *
     * @param WebsiteInterface|null $website
     * @return bool
     */
    public function isRestrictionActive(WebsiteInterface $website = null): bool
    {
        if ($this->isFeaturesEnabled($website)) {
            return false;
        }

        $excludedPagesBelongToWebCatalog = $this->isExcludedPagesBelongToWebCatalog($website);
        $includedPagesNotBelongToWebCatalog = $this->isIncludedPagesNotBelongToWebCatalog($website);

        return ($excludedPagesBelongToWebCatalog && $includedPagesNotBelongToWebCatalog) ||
            (!$excludedPagesBelongToWebCatalog && !$includedPagesNotBelongToWebCatalog);
    }

    /**
     * Exclude direct URLs of landing pages option.
     *
     * @param WebsiteInterface|null $website
     * @return bool
     */
    private function isExcludedPagesBelongToWebCatalog(WebsiteInterface $website = null): bool
    {
        return $this->configManager->get(
            self::EXCLUDE_WEB_CATALOG_LANDING_PAGES,
            true,
            false,
            $website
        );
    }

    /**
     * Include landing pages that are not used in web catalog option.
     *
     * @param WebsiteInterface|null $website
     * @return bool
     */
    private function isIncludedPagesNotBelongToWebCatalog(WebsiteInterface $website = null): bool
    {
        return $this->configManager->get(
            self::INCLUDE_NOT_IN_WEB_CATALOG_LANDING_PAGES,
            false,
            false,
            $website
        );
    }
}
