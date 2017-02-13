<?php

namespace Oro\Bundle\SEOBundle\Tools;

use Oro\Bundle\SEOBundle\Model\WebsiteInterface;
use Oro\Bundle\SEOBundle\Provider\SitemapUrlProviderRegistry;

class SitemapDumper implements SitemapDumperInterface
{
    /**
     * @var SitemapUrlProviderRegistry
     */
    private $providerRegistry;

    /**
     * @param SitemapUrlProviderRegistry $providerRegistry
     */
    public function __construct(SitemapUrlProviderRegistry $providerRegistry)
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
