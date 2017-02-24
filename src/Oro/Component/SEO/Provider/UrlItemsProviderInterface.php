<?php

namespace Oro\Component\SEO\Provider;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;
use Oro\Component\Website\WebsiteInterface;

interface UrlItemsProviderInterface
{
    /**
     * @param WebsiteInterface $website
     * @return array|UrlItemInterface[]
     */
    public function getUrlItems(WebsiteInterface $website);
}
