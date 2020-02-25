<?php

namespace Oro\Bundle\SEOBundle\Sitemap\EventListener;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Sitemap\Dumper\SitemapDumper;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\Exception\LogicException;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Manager\RobotsTxtSitemapManager;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageFactory;

/**
 * Add sitemap index to robots.txt when sitemaps are generated.
 */
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
                $url = sprintf(
                    '%s/%s/%s/%s',
                    $this->sitemapDir,
                    $event->getWebsite()->getId(),
                    SitemapFilesystemAdapter::ACTUAL_VERSION,
                    $indexFile->getFilename()
                );

                $domainUrl = rtrim($this->canonicalUrlGenerator->getCanonicalDomainUrl($event->getWebsite()), '/');
                // Sitemaps are placed in root folder of domain, additional path should be removed
                $baseDomainUrl = str_replace(parse_url($domainUrl, PHP_URL_PATH), '', $domainUrl);

                $this->robotsTxtSitemapManager->addSitemap(
                    $this->canonicalUrlGenerator->createUrl($baseDomainUrl, $url)
                );
            }

            $this->robotsTxtSitemapManager->flush();
        }
    }
}
