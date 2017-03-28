<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Component\Website\WebsiteInterface;

class CategoryUrlItemsProvider extends UrlItemsProvider
{
    use FeatureCheckerHolderTrait;

    /**
     * {@inheritdoc}
     */
    public function getUrlItems(WebsiteInterface $website, $version)
    {
        if (!$this->isFeaturesEnabled($website)) {
            return [];
        }

        return parent::getUrlItems($website, $version);
    }
}
