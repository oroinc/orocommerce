<?php

namespace Oro\Bundle\SEOBundle\Tools;

use Oro\Bundle\SEOBundle\Provider\UrlItemsProviderRegistry;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Oro\Component\Website\WebsiteInterface;

class SitemapDumper implements SitemapDumperInterface
{
    /**
     * @var UrlItemsProviderRegistry
     */
    private $providerRegistry;

    /**
     * @param UrlItemsProviderRegistry $providerRegistry
     */
    public function __construct(UrlItemsProviderRegistry $providerRegistry)
    {
        $this->providerRegistry = $providerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function dump(WebsiteInterface $website, $type = null)
    {
        // some process here
    }
}
