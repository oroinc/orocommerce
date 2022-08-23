<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Component\Website\WebsiteInterface;

/**
 * Provides Switchable UrlItems for sitemap generation
 */
class SwitchableUrlItemsProvider extends UrlItemsProvider
{
    private SwitchableUrlItemsProviderInterface $provider;

    public function setProvider(SwitchableUrlItemsProviderInterface $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlItems(WebsiteInterface $website, $version)
    {
        if ($this->provider->isUrlItemsExcluded($website)) {
            return [];
        }

        return parent::getUrlItems($website, $version);
    }
}
