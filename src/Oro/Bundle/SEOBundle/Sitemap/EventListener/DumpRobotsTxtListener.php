<?php

namespace Oro\Bundle\SEOBundle\Sitemap\EventListener;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtManager;
use Oro\Bundle\SEOBundle\Sitemap\Dumper\SitemapDumper;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\Exception\LogicException;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageFactory;

class DumpRobotsTxtListener
{
    /**
     * @var RobotsTxtManager
     */
    private $robotsTxtManager;

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
     * @param RobotsTxtManager $robotsTxtManager
     * @param CanonicalUrlGenerator $canonicalUrlGenerator
     * @param SitemapFilesystemAdapter $sitemapFilesystemAdapter
     * @param string $sitemapDir
     */
    public function __construct(
        RobotsTxtManager $robotsTxtManager,
        CanonicalUrlGenerator $canonicalUrlGenerator,
        SitemapFilesystemAdapter $sitemapFilesystemAdapter,
        $sitemapDir
    ) {
        $this->robotsTxtManager = $robotsTxtManager;
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

            if ($indexFiles->count() > 1) {
                throw new LogicException('There are more than one index files.');
            }

            /** @var \SplFileInfo $indexFile */
            foreach ($indexFiles as $indexFile) {
                break;
            }

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

            $this->robotsTxtManager->changeByKeyword(RobotsTxtManager::KEYWORD_SITEMAP, $absoluteUrl);
        }
    }
}
