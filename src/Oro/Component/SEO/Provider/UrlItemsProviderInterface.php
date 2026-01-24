<?php

namespace Oro\Component\SEO\Provider;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;
use Oro\Component\Website\WebsiteInterface;

/**
 * Defines the contract for providers that supply URL items for sitemap generation.
 *
 * Implementations provide URL items for a specific website and version, returning them
 * as an array or generator to support efficient processing of large datasets.
 */
interface UrlItemsProviderInterface
{
    /**
     * @param WebsiteInterface $website
     * @param int $version
     * @return array|UrlItemInterface[]|\Generator
     */
    public function getUrlItems(WebsiteInterface $website, $version);
}
