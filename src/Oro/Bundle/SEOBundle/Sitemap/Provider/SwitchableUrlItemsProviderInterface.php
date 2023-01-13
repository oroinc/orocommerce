<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Component\Website\WebsiteInterface;

/**
 * Provides exclusion of UrlItems during sitemap generation
 */
interface SwitchableUrlItemsProviderInterface
{
    public function isUrlItemsExcluded(WebsiteInterface $website = null): bool;
}
