<?php

namespace Oro\Bundle\SEOBundle\Tools;

use Oro\Component\SEO\Provider\SitemapUrlProviderInterface;
use Oro\Component\SEO\Provider\VersionAwareInterface;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Oro\Component\Website\WebsiteInterface;

class SitemapIndexDumper implements SitemapDumperInterface
{
    const SITEMAP_FILENAME_TEMPLATE = '';

    /**
     * @var SitemapUrlProviderInterface
     */
    private $provider;

    /**
     * @var SitemapStorageFactory
     */
    private $sitemapStorageFactory;

    /**
     * @var SitemapFilesystemAdapter
     */
    private $filesystemAdapter;

    /**
     * @param SitemapUrlProviderInterface $provider
     * @param SitemapStorageFactory $sitemapStorageFactory
     * @param SitemapFilesystemAdapter $filesystemAdapter
     */
    public function __construct(
        SitemapUrlProviderInterface $provider,
        SitemapStorageFactory $sitemapStorageFactory,
        SitemapFilesystemAdapter $filesystemAdapter
    ) {
        $this->provider = $provider;
        $this->sitemapStorageFactory = $sitemapStorageFactory;
        $this->filesystemAdapter = $filesystemAdapter;
    }

    /**
     * {@inheritdoc}
     */
    public function dump(WebsiteInterface $website, $version, $type = null)
    {
        $urlsStorage = $this->sitemapStorageFactory->createUrlsStorage();
        if ($this->provider instanceof VersionAwareInterface) {
            $this->provider->setVersion($version);
        }

        $fileNumber = 1;
        foreach ($this->provider->getUrlItems($website) as $urlItem) {
            $itemAdded = $urlsStorage->addUrlItem($urlItem);
            if (!$itemAdded) {
                $this->filesystemAdapter->dumpSitemapStorage(
                    $this->createFileName($fileNumber++),
                    $website,
                    $version,
                    $urlsStorage
                );

                $urlsStorage = $this->sitemapStorageFactory->createUrlsStorage();
                $urlsStorage->addUrlItem($urlItem);
            }
        }

        $this->filesystemAdapter->dumpSitemapStorage(
            $this->createFileName($fileNumber),
            $website,
            $version,
            $urlsStorage
        );
    }

    /**
     * @param int $fileNumber
     * @return string
     */
    private function createFileName($fileNumber)
    {
        return sprintf('sitemap-index-%d.xml', $fileNumber);
    }
}
