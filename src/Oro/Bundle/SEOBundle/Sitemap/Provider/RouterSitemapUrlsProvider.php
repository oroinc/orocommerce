<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Sitemap URL Items Provider for specific routes.
 */
class RouterSitemapUrlsProvider implements UrlItemsProviderInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private CanonicalUrlGenerator $canonicalUrlGenerator;
    /** @var string[] */
    private array $routes;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param CanonicalUrlGenerator $canonicalUrlGenerator
     * @param string[]              $routes
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        CanonicalUrlGenerator $canonicalUrlGenerator,
        array $routes
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->routes = $routes;
    }

    /**
     * {@inheritDoc}
     */
    public function getUrlItems(WebsiteInterface $website, $version)
    {
        foreach ($this->getAllowUrls() as $url) {
            yield new UrlItem($this->canonicalUrlGenerator->getAbsoluteUrl($url, $website));
        }
    }

    /**
     * @return string[]
     */
    private function getAllowUrls(): array
    {
        $urls = [];
        foreach ($this->routes as $url) {
            $urls[] = $this->urlGenerator->generate($url);
        }

        return $urls;
    }
}
