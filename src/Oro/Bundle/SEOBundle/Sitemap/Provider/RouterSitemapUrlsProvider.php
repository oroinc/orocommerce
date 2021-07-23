<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\Routing\Router;

class RouterSitemapUrlsProvider implements UrlItemsProviderInterface
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var CanonicalUrlGenerator
     */
    private $canonicalUrlGenerator;

    /**
     * @var array
     */
    private $routes;

    public function __construct(Router $router, CanonicalUrlGenerator $canonicalUrlGenerator, array $routes)
    {
        $this->router = $router;
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->routes = $routes;
    }

    /**
     * {@inheritDoc}
     */
    public function getUrlItems(WebsiteInterface $website, $version)
    {
        foreach ($this->getAllowUrls() as $url) {
            $absoluteUrl = $this->canonicalUrlGenerator->getAbsoluteUrl($url, $website);

            yield new UrlItem($absoluteUrl);
        }
    }

    /**
     * @return array
     */
    private function getAllowUrls()
    {
        $urls = [];
        foreach ($this->routes as $url) {
            $urls[] = $this->router->generate($url);
        }
        return $urls;
    }
}
