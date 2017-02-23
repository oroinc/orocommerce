<?php

namespace Oro\Bundle\SEOBundle\Provider;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Component\SEO\Provider\SitemapUrlProviderInterface;
use Oro\Component\Website\WebsiteInterface;

class DummySitemapProvider implements SitemapUrlProviderInterface
{
    public function getUrlItems(WebsiteInterface $website)
    {
        for ($i = 0; $i < 100; $i++) {
            yield new UrlItem('http://test.com/url-' . $i);
        }
    }
}
