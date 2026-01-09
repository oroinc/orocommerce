<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Component\Website\WebsiteInterface;

/**
 * Provides URL items for product categories in the sitemap.
 *
 * This provider extends the base {@see UrlItemsProvider} to generate sitemap entries for product categories.
 * It respects feature toggles to conditionally include categories in the sitemap based on whether
 * the category feature is enabled for the website.
 */
class CategoryUrlItemsProvider extends UrlItemsProvider
{
    use FeatureCheckerHolderTrait;

    #[\Override]
    public function getUrlItems(WebsiteInterface $website, $version)
    {
        if (!$this->isFeaturesEnabled($website)) {
            return [];
        }

        return parent::getUrlItems($website, $version);
    }
}
