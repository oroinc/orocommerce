<?php

namespace Oro\Bundle\SEOBundle\Tools;

use Oro\Bundle\SEOBundle\Provider\UrlItemsProviderRegistry;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Oro\Component\Website\WebsiteInterface;

class SitemapDumper implements SitemapDumperInterface
{
    const SITEMAP_FILENAME_TEMPLATE = 'sitemap-%s-%s.xml';
    const SITEMAPS_TEMP_DIR = 'sitemaps';

    /**
     * @var UrlItemsProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var SitemapStorageFactory
     */
    private $sitemapStorageFactory;

    /**
     * @var SitemapFileWriterInterface
     */
    private $sitemapFileWriter;

    /**
     * @var string
     */
    private $kernelRootDir;

    /**
     * @param UrlItemsProviderRegistry $providerRegistry
     * @param SitemapStorageFactory $sitemapStorageFactory
     * @param SitemapFileWriterInterface $sitemapFileWriter
     * @param string $kernelRootDir
     */
    public function __construct(
        UrlItemsProviderRegistry $providerRegistry,
        SitemapStorageFactory $sitemapStorageFactory,
        SitemapFileWriterInterface $sitemapFileWriter,
        $kernelRootDir
    ) {
        $this->providerRegistry = $providerRegistry;
        $this->sitemapStorageFactory = $sitemapStorageFactory;
        $this->sitemapFileWriter = $sitemapFileWriter;
        $this->kernelRootDir = $kernelRootDir;
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

        $tmpDir = $this->getSitemapsTmpDir($website);
        foreach ($providers as $providerType => $provider) {
            $urlsStorage = $this->sitemapStorageFactory->createUrlsStorage();

            $fileNumber = 1;
            foreach ($provider->getUrlItems($website) as $urlItem) {
                $itemAdded = $urlsStorage->addUrlItem($urlItem);
                if (!$itemAdded) {
                    $this->sitemapFileWriter->saveSitemap(
                        $urlsStorage->getContents(),
                        $this->createFileName($tmpDir, $providerType, $fileNumber++)
                    );

                    $urlsStorage = $this->sitemapStorageFactory->createUrlsStorage();
                    $urlsStorage->addUrlItem($urlItem);
                }
            }

            $this->sitemapFileWriter->saveSitemap(
                $urlsStorage->getContents(),
                $this->createFileName($tmpDir, $providerType, $fileNumber)
            );
        }

        return $tmpDir;
    }

    /**
     * @param WebsiteInterface $website
     * @return string
     */
    private function getSitemapsTmpDir(WebsiteInterface $website)
    {
        return sprintf('%s/%s/%s', $this->kernelRootDir, self::SITEMAPS_TEMP_DIR, $website->getId());
    }

    /**
     * @param string $dirPath
     * @param string $providerType
     * @param string $fileNumber
     * @return string
     */
    private function createFileName($dirPath, $providerType, $fileNumber)
    {
        return sprintf('%s/%s', $dirPath, sprintf(static::SITEMAP_FILENAME_TEMPLATE, $providerType, $fileNumber));
    }
}
