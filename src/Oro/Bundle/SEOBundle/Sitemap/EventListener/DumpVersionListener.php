<?php

namespace Oro\Bundle\SEOBundle\Sitemap\EventListener;

use Oro\Bundle\SEOBundle\Provider\WebsiteForSitemapProviderInterface;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;

/**
 * Dumps a file contains a generated sitemap version.
 */
class DumpVersionListener
{
    /** @var SitemapFilesystemAdapter */
    private $filesystemAdapter;

    /** @var WebsiteForSitemapProviderInterface $websiteForSitemapProvider */
    private $websiteForSitemapProvider;

    public function __construct(
        SitemapFilesystemAdapter $filesystemAdapter,
        WebsiteForSitemapProviderInterface $websiteForSitemapProvider
    ) {
        $this->filesystemAdapter = $filesystemAdapter;
        $this->websiteForSitemapProvider = $websiteForSitemapProvider;
    }

    public function onSitemapDumpStorage(OnSitemapDumpFinishEvent $event): void
    {
        $website = $event->getWebsite();
        $availableWebsites = $this->websiteForSitemapProvider->getAvailableWebsites();

        if (!in_array($website, $availableWebsites, true)) {
            return;
        }

        $this->filesystemAdapter->dumpVersion($website, $event->getVersion());
    }
}
