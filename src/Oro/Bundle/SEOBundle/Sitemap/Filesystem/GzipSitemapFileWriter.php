<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

/**
 * Compresses sitemap related data using gzip before it is written to files.
 */
class GzipSitemapFileWriter implements SitemapFileWriterInterface
{
    /** @var SitemapFileWriterInterface */
    private $sitemapFileWriter;

    /** @var array|string[] */
    private $skipCompressionForProviders = [];

    public function __construct(SitemapFileWriterInterface $sitemapFileWriter)
    {
        $this->sitemapFileWriter = $sitemapFileWriter;
    }

    /**
     * Use this function to disable compression for a given provider types.
     *
     * Use DI parameter oro_seo.sitemap.skip_compression_for_providers (empty array by default)
     * to override the list of skipped types.
     * The parameter may be overridden for example via bundle `services.yml`, application `parameters.yml`, etc.
     *
     * Example:
     * ```yaml
     * parameters:
     *     oro_seo.sitemap.skip_compression_for_providers: ['index']
     * ```
     */
    public function skipCompressionForProviderTypes(array $providerTypes)
    {
        $this->skipCompressionForProviders = $providerTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function saveSitemap(string $content, string $path): string
    {
        $gzippedContent = false;
        if (!$this->isCompressionSkipped($path)) {
            $gzippedContent = gzencode($content);
        }

        if (false === $gzippedContent) {
            return $this->sitemapFileWriter->saveSitemap($content, $path);
        }

        return $this->sitemapFileWriter->saveSitemap($gzippedContent, $path . '.gz');
    }

    private function isCompressionSkipped(string $path):bool
    {
        $fileName = basename($path);
        foreach ($this->skipCompressionForProviders as $providerType) {
            if (strpos($fileName, 'sitemap-' . $providerType) === 0) {
                return true;
            }
        }

        return false;
    }
}
