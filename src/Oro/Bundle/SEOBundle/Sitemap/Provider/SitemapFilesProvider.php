<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Dumper\SitemapDumper;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\Finder\Finder;

class SitemapFilesProvider implements UrlItemsProviderInterface
{
    /**
     * @var SitemapFilesystemAdapter
     */
    private $filesystemAdapter;

    /**
     * @var CanonicalUrlGenerator
     */
    private $canonicalUrlGenerator;

    /**
     * @var string
     */
    private $webPath;

    /**
     * @param SitemapFilesystemAdapter $filesystemAdapter
     * @param CanonicalUrlGenerator $canonicalUrlGenerator
     * @param string $webPath
     */
    public function __construct(
        SitemapFilesystemAdapter $filesystemAdapter,
        CanonicalUrlGenerator $canonicalUrlGenerator,
        $webPath
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
        $files = $this->filesystemAdapter->getSitemapFiles($website, $version);
        if ($files instanceof Finder) {
            $files->notName(SitemapDumper::getFilenamePattern('index'));
        }

        foreach ($files as $file) {
            $url = sprintf('%s/%d/%d/%s', $this->webPath, $website->getId(), $version, $file->getFilename());

            $mTime = \DateTime::createFromFormat('U', $file->getMTime(), new \DateTimeZone('UTC'));
            yield new UrlItem($this->canonicalUrlGenerator->getAbsoluteUrl($url, $website), $mTime);
        }
    }
}
