<?php

namespace Oro\Bundle\SEOBundle\Provider;

use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;

/**
 * Default implementation of WebsiteForSitemapProviderInterface that use WebsiteProviderInterface
 * to get the list of websites.
 */
class WebsiteForSitemapProvider implements WebsiteForSitemapProviderInterface
{
    /** @var WebsiteProviderInterface */
    private $websiteProvider;

    public function __construct(WebsiteProviderInterface $websiteProvider)
    {
        $this->websiteProvider = $websiteProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableWebsites(): array
    {
        return $this->websiteProvider->getWebsites();
    }
}
