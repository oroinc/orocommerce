<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Dumper;

use Oro\Bundle\SEOBundle\Provider\UrlItemsProviderRegistry;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageFactory;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageInterface;
use Oro\Component\SEO\Provider\VersionAwareInterface;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Oro\Component\Website\WebsiteInterface;

class SitemapDumper implements SitemapDumperInterface
{
    const SITEMAP_FILENAME_TEMPLATE = 'sitemap-%s-%s.xml';

    /**
     * @var UrlItemsProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var SitemapStorageFactory
     */
    private $sitemapStorageFactory;

    /**
     * @var SitemapFilesystemAdapter
     */
    private $filesystemAdapter;

    /**
     * @var string
     */
    private $storageType;

    /**
     * @param UrlItemsProviderRegistry $providerRegistry
     * @param SitemapStorageFactory $sitemapStorageFactory
     * @param SitemapFilesystemAdapter $filesystemAdapter
     * @param string $storageType
     */
    public function __construct(
        UrlItemsProviderRegistry $providerRegistry,
        SitemapStorageFactory $sitemapStorageFactory,
        SitemapFilesystemAdapter $filesystemAdapter,
        $storageType
    ) {
        $this->providerRegistry = $providerRegistry;
        $this->sitemapStorageFactory = $sitemapStorageFactory;
        $this->filesystemAdapter = $filesystemAdapter;
        $this->storageType = $storageType;
    }

    /**
     * @param string $type
     * @return string
     */
    public static function getFilenamePattern($type = '*')
    {
        return self::createFileName($type, '*') . '*';
    }

    /**
     * {@inheritdoc}
     */
    public function dump(WebsiteInterface $website, $version, $type = null)
    {
        if ($type) {
            $providers[$type] = $this->providerRegistry->getProviderByName($type);
        } else {
            $providers = $this->providerRegistry->getProviders();
        }

        foreach ($providers as $providerType => $provider) {
            if ($provider instanceof VersionAwareInterface) {
                $provider->setVersion($version);
            }
            $urlsStorage = $this->createUrlsStorage();

            $fileNumber = 1;
            foreach ($provider->getUrlItems($website) as $urlItem) {
                $itemAdded = $urlsStorage->addUrlItem($urlItem);
                if (!$itemAdded) {
                    $this->filesystemAdapter->dumpSitemapStorage(
                        $this->createFileName($providerType, $fileNumber++),
                        $website,
                        $version,
                        $urlsStorage
                    );

                    $urlsStorage = $this->createUrlsStorage();
                    $urlsStorage->addUrlItem($urlItem);
                }
            }

            $this->filesystemAdapter->dumpSitemapStorage(
                $this->createFileName($providerType, $fileNumber),
                $website,
                $version,
                $urlsStorage
            );
        }
    }

    /**
     * @param string $providerType
     * @param string $fileNumber
     * @return string
     */
    private static function createFileName($providerType, $fileNumber)
    {
        return sprintf(static::SITEMAP_FILENAME_TEMPLATE, $providerType, $fileNumber);
    }

    /**
     * @return SitemapStorageInterface
     */
    private function createUrlsStorage()
    {
        return $this->sitemapStorageFactory->createUrlsStorage($this->storageType);
    }
}
