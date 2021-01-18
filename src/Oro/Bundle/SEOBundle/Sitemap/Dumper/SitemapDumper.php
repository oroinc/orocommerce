<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Dumper;

use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageFactory;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageInterface;
use Oro\Bundle\SEOBundle\Sitemap\Website\WebsiteUrlProvidersServiceInterface;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The main implementation of dumping sitemap related data to files.
 */
class SitemapDumper implements SitemapDumperInterface
{
    const SITEMAP_FILENAME_TEMPLATE = 'sitemap-%s-%s.xml';

    /**
     * @var SitemapStorageFactory
     */
    private $sitemapStorageFactory;

    /**
     * @var SitemapFilesystemAdapter
     */
    private $filesystemAdapter;

    /**
     * @var WebsiteUrlProvidersServiceInterface
     */
    private $websiteUrlProvidersService;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var string
     */
    private $storageType;

    /**
     * @param WebsiteUrlProvidersServiceInterface $websiteUrlProvidersService
     * @param SitemapStorageFactory $sitemapStorageFactory
     * @param SitemapFilesystemAdapter $filesystemAdapter
     * @param EventDispatcherInterface $eventDispatcher
     * @param string $storageType
     */
    public function __construct(
        WebsiteUrlProvidersServiceInterface $websiteUrlProvidersService,
        SitemapStorageFactory $sitemapStorageFactory,
        SitemapFilesystemAdapter $filesystemAdapter,
        EventDispatcherInterface $eventDispatcher,
        $storageType
    ) {
        $this->websiteUrlProvidersService = $websiteUrlProvidersService;
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
        $providers = $this->websiteUrlProvidersService->getWebsiteProvidersIndexedByNames($website);
        if (isset($providers[$type])) {
            $provider[$type] = $providers[$type];
            $providers = $provider;
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
                        $urlsStorage
                    );

                    $urlsStorage = $this->createUrlsStorage();
                    $urlsStorage->addUrlItem($urlItem);
                }
            }

            $this->filesystemAdapter->dumpSitemapStorage(
                static::createFileName($providerType, $fileNumber),
                $website,
                $urlsStorage
            );
        }

        $event = new OnSitemapDumpFinishEvent($website, $version);
        $this->eventDispatcher->dispatch(
            $event,
            sprintf('%s.%s', OnSitemapDumpFinishEvent::EVENT_NAME, $this->storageType)
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
