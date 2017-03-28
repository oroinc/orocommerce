<?php

namespace Oro\Bundle\SEOBundle\Sitemap\EventListener;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Sitemap\Manager\RobotsTxtSitemapManager;
use Oro\Bundle\SEOBundle\Sitemap\Dumper\SitemapDumper;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\Exception\LogicException;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageFactory;

class DumpRobotsTxtListener
{
    /**
     * @var RobotsTxtSitemapManager
     */
    private $robotsTxtSitemapManager;

    /**
     * @var CanonicalUrlGenerator
     */
    private $canonicalUrlGenerator;

    /**
     * @var SitemapFilesystemAdapter
     */
    private $sitemapFilesystemAdapter;

    /**
     * @var string
     */
    private $sitemapDir;

    /**
     * @param RobotsTxtSitemapManager $robotsTxtSitemapManager
     * @param CanonicalUrlGenerator $canonicalUrlGenerator
     * @param SitemapFilesystemAdapter $sitemapFilesystemAdapter
     * @param string $sitemapDir
     */
    public function __construct(
        RobotsTxtSitemapManager $robotsTxtSitemapManager,
        CanonicalUrlGenerator $canonicalUrlGenerator,
        SitemapFilesystemAdapter $sitemapFilesystemAdapter,
        $sitemapDir
    ) {
        $this->robotsTxtSitemapManager = $robotsTxtSitemapManager;
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->sitemapFilesystemAdapter = $sitemapFilesystemAdapter;
        $this->sitemapDir = $sitemapDir;
    }

    /**
     * @param OnSitemapDumpFinishEvent $event
     */
    public function onSitemapDumpStorage(OnSitemapDumpFinishEvent $event)
    {
        if ($event->getWebsite()->isDefault()) {
            $indexFiles = $this->sitemapFilesystemAdapter->getSitemapFiles(
                $event->getWebsite(),
                SitemapFilesystemAdapter::ACTUAL_VERSION,
                SitemapDumper::getFilenamePattern(SitemapStorageFactory::TYPE_SITEMAP_INDEX)
            );

            if (!$indexFiles->count()) {
                throw new LogicException('Cannot find sitemap index file.');
            }

            /** @var \SplFileInfo $indexFile */
            foreach ($indexFiles as $indexFile) {
                $absoluteUrl = $this->canonicalUrlGenerator->getAbsoluteUrl(
                    sprintf(
                        '%s/%s/%s/%s',
                        $this->sitemapDir,
                        $event->getWebsite()->getId(),
                        SitemapFilesystemAdapter::ACTUAL_VERSION,
                        $indexFile->getFilename()
                    ),
                    $event->getWebsite()
                );

                $this->robotsTxtSitemapManager->addSitemap($absoluteUrl);
            }

            $this->robotsTxtSitemapManager->flush();
        }
    }
}
