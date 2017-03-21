<?php

namespace Oro\Component\SEO\Provider;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;
use Oro\Component\Website\WebsiteInterface;

interface UrlItemsProviderInterface
{
    /**
     * @param WebsiteInterface $website
     * @param int $version
     * @return array|UrlItemInterface[]|\Generator
     */
    public function getUrlItems(WebsiteInterface $website, $version);
}
