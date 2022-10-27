<?php

namespace Oro\Bundle\SEOBundle\Sitemap\EventListener;

use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;

/**
 * Dumps a file contains a generated sitemap version.
 */
class DumpVersionListener
{
    /** @var SitemapFilesystemAdapter */
    private $filesystemAdapter;

    public function __construct(SitemapFilesystemAdapter $filesystemAdapter)
    {
        $this->filesystemAdapter = $filesystemAdapter;
    }

    public function onSitemapDumpStorage(OnSitemapDumpFinishEvent $event): void
    {
        $this->filesystemAdapter->dumpVersion($event->getWebsite(), $event->getVersion());
    }
}
