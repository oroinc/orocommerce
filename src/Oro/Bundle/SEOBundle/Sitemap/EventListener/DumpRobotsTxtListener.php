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
 * Adds a link to the sitemap index file into the robots.txt file.
 */
class DumpRobotsTxtListener
{
    /** @var RobotsTxtSitemapManager */
    private $robotsTxtSitemapManager;

    /** @var CanonicalUrlGenerator */
    private $canonicalUrlGenerator;

    /** @var SitemapFilesystemAdapter */
    private $sitemapFilesystemAdapter;

    /** @var string */
    private $sitemapDir;

    public function __construct(
        RobotsTxtSitemapManager $robotsTxtSitemapManager,
        CanonicalUrlGenerator $canonicalUrlGenerator,
        SitemapFilesystemAdapter $sitemapFilesystemAdapter,
        string $sitemapDir
    ) {
        $this->robotsTxtSitemapManager = $robotsTxtSitemapManager;
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->sitemapFilesystemAdapter = $sitemapFilesystemAdapter;
        $this->sitemapDir = $sitemapDir;
    }

    public function onSitemapDumpStorage(OnSitemapDumpFinishEvent $event): void
    {
        $website = $event->getWebsite();
        $files = $this->sitemapFilesystemAdapter->getSitemapFiles(
            $website,
            SitemapDumper::getFilenamePattern(SitemapStorageFactory::TYPE_SITEMAP_INDEX)
        );

        if (empty($files)) {
            throw new LogicException('Cannot find sitemap index file.');
        }

        foreach ($files as $file) {
            $url = sprintf(
                '%s/%s/%s',
                $this->sitemapDir,
                $event->getWebsite()->getId(),
                pathinfo($file->getName(), PATHINFO_BASENAME)
            );

            $domainUrl = rtrim($this->canonicalUrlGenerator->getCanonicalDomainUrl($website), '/');
            // Sitemaps are placed in root folder of domain, additional path should be removed
            $baseDomainUrl = str_replace(parse_url($domainUrl, PHP_URL_PATH), '', $domainUrl);

            $this->robotsTxtSitemapManager->addSitemap(
                $this->canonicalUrlGenerator->createUrl($baseDomainUrl, $url)
            );
        }

        $this->robotsTxtSitemapManager->flush($website);
    }
}
