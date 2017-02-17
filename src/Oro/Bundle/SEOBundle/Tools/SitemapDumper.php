<?php

namespace Oro\Bundle\SEOBundle\Tools;

use Oro\Bundle\SEOBundle\Model\UrlSet;
use Oro\Bundle\SEOBundle\Provider\SitemapUrlProviderRegistry;
use Oro\Bundle\SEOBundle\Tools\Encoder\UrlItemEncoder;
use Oro\Bundle\SEOBundle\Tools\Encoder\UrlSetEncoder;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class SitemapDumper implements SitemapDumperInterface
{
    /**
     * @var SitemapUrlProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $sitemapFullLocation;

    /**
     * @var UrlItemEncoder
     */
    private $urlItemEncoder;

    /**
     * @var UrlSetEncoder
     */
    private $urlSetEncoder;

    /**
     * @param SitemapUrlProviderRegistry $providerRegistry
     * @param Filesystem $filesystem
     * @param string $kernelRootDir
     */
    public function __construct(
        SitemapUrlProviderRegistry $providerRegistry,
        Filesystem $filesystem,
        $kernelRootDir
    ) {
        $this->providerRegistry = $providerRegistry;
        $this->filesystem = $filesystem;
        $this->sitemapFullLocation = sprintf('%s/%s', $kernelRootDir, static::SITEMAP_LOCATION); 

        $this->urlItemEncoder = new UrlItemEncoder();
        $this->urlSetEncoder = new UrlSetEncoder();
    }

    /**
     * {@inheritdoc}
     */
    public function dump(WebsiteInterface $website, $type = null)
    {
        if ($type) {
            $providers[$type] = $this->providerRegistry->getProviderByName($type);
        } else {
            $providers = $this->providerRegistry->getProviders();
        }

        $tmpDir = sprintf('%s/%s/%s', sys_get_temp_dir(), md5(time()), $website->getId());
        foreach ($providers as $name => $provider) {
            $urlItems = $provider->getUrlItems($website);

            $urlSetNumber = 0;
            while (count($urlItems) > 0) {
                $urlSetNumber++;
                $urlSet = $this->createUrlSet($urlItems);

                $content = $this->urlSetEncoder->encode($urlSet);
                $filePath = sprintf(static::SITEMAP_FILENAME_TEMPLATE, $tmpDir, $name, $urlSetNumber);
                $this->writeFile($filePath, $content);
            }
        }

        $sitemapDir = sprintf('%s/%s', $this->sitemapFullLocation, $website->getId());
        $this->moveSitemapFromTmp($tmpDir, $sitemapDir);
    }

    /**
     * @param $urlItems
     * @return UrlSet
     */
    private function createUrlSet(&$urlItems)
    {
        $urlSet = new UrlSet();
        foreach ($urlItems as $key => $urlItem) {
            if (!$urlSet->addUrlItem($urlItem)) {
                break;
            }
            
            unset($urlItems[$key]);
        }
        
        return $urlSet;
    }

    /**
     * @param string $path
     * @param string $content
     */
    private function writeFile($path, $content)
    {
        try {
            $this->filesystem->dumpFile($path, $content, 0755);
        } catch (IOExceptionInterface $e) {
            throw new \RuntimeException(
                sprintf('An error occurred while creating file at %s', $e->getPath())
            );
        }
    }

    /**
     * @param string $tmpPath
     * @param string $path
     */
    private function moveSitemapFromTmp($tmpPath, $path)
    {
        try {
            $this->filesystem->remove($path);
            $this->filesystem->copy($tmpPath, $path);
            $this->filesystem->remove($tmpPath);
        } catch (IOExceptionInterface $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }
}
