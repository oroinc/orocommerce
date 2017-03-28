<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Dumper;

use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistry;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageFactory;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageInterface;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var string
     */
    private $storageType;

    /**
     * @param UrlItemsProviderRegistry $providerRegistry
     * @param SitemapStorageFactory $sitemapStorageFactory
     * @param SitemapFilesystemAdapter $filesystemAdapter
     * @param EventDispatcherInterface $eventDispatcher
     * @param string $storageType
     */
    public function __construct(
        UrlItemsProviderRegistry $providerRegistry,
        SitemapStorageFactory $sitemapStorageFactory,
        SitemapFilesystemAdapter $filesystemAdapter,
        EventDispatcherInterface $eventDispatcher,
        $storageType
    ) {
        $this->providerRegistry = $providerRegistry;
        $this->sitemapStorageFactory = $sitemapStorageFactory;
        $this->filesystemAdapter = $filesystemAdapter;
        $this->eventDispatcher = $eventDispatcher;
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
            $urlsStorage = $this->createUrlsStorage();

            $fileNumber = 1;
            foreach ($provider->getUrlItems($website, $version) as $urlItem) {
                $itemAdded = $urlsStorage->addUrlItem($urlItem);
                if (!$itemAdded) {
                    $this->filesystemAdapter->dumpSitemapStorage(
                        static::createFileName($providerType, $fileNumber++),
                        $website,
                        $version,
                        $urlsStorage
                    );

                    $urlsStorage = $this->createUrlsStorage();
                    $urlsStorage->addUrlItem($urlItem);
                }
            }

            $this->filesystemAdapter->dumpSitemapStorage(
                static::createFileName($providerType, $fileNumber),
                $website,
                $version,
                $urlsStorage
            );
        }

        $event = new OnSitemapDumpFinishEvent($website, $version);
        $this->eventDispatcher->dispatch(
            sprintf('%s.%s', OnSitemapDumpFinishEvent::EVENT_NAME, $this->storageType),
            $event
        );
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
