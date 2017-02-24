<?php

namespace Oro\Bundle\SEOBundle\Provider;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Tools\SitemapFilesystemAdapter;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\SEO\Provider\VersionAwareInterface;
use Oro\Component\Website\WebsiteInterface;

class SitemapIndexProvider implements UrlItemsProviderInterface, VersionAwareInterface
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
     * @var string
     */
    private $version;

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
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @param WebsiteInterface $website
     * @return \Generator
     */
    public function getUrlItems(WebsiteInterface $website)
    {
        foreach ($this->filesystemAdapter->getSitemapFiles($website, $this->version) as $file) {
            $url = $this->canonicalUrlGenerator->getAbsoluteUrl($this->webPath . '/' . $file, $website);

            yield new UrlItem($url);
        }
    }
}
