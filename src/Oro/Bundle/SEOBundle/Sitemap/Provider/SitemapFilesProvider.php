<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Dumper\SitemapDumper;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageFactory;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;

/**
 * Sitemap URL Items Provider for sitemap index entities.
 */
class SitemapFilesProvider implements UrlItemsProviderInterface
{
    /** @var SitemapFilesystemAdapter */
    private $filesystemAdapter;

    /** @var CanonicalUrlGenerator */
    private $canonicalUrlGenerator;

    /** @var string */
    private $webPath;

    public function __construct(
        SitemapFilesystemAdapter $filesystemAdapter,
        CanonicalUrlGenerator $canonicalUrlGenerator,
        string $webPath
    ) {
        $this->filesystemAdapter = $filesystemAdapter;
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->webPath = $webPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlItems(WebsiteInterface $website, $version)
    {
        $files = $this->filesystemAdapter->getSitemapFiles(
            $website,
            null,
            SitemapDumper::getFilenamePattern(SitemapStorageFactory::TYPE_SITEMAP_INDEX)
        );

        foreach ($files as $file) {
            $url = sprintf(
                '%s/%d/%s',
                $this->webPath,
                $website->getId(),
                pathinfo($file->getName(), PATHINFO_BASENAME)
            );

            $mTime = \DateTime::createFromFormat('U', $file->getMtime(), new \DateTimeZone('UTC'));

            yield new UrlItem($this->getSitemapFileUrl($website, $url), $mTime);
        }
    }

    protected function getSitemapFileUrl(WebsiteInterface $website, string $url): string
    {
        $domainUrl = rtrim($this->canonicalUrlGenerator->getCanonicalDomainUrl($website), '/');
        // Sitemaps are placed in root folder of domain, additional path should be removed
        $baseDomainUrl = str_replace(parse_url($domainUrl, PHP_URL_PATH), '', $domainUrl);

        return $this->canonicalUrlGenerator->createUrl($baseDomainUrl, $url);
    }
}
