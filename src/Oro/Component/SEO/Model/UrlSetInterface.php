<?php

namespace Oro\Component\SEO\Model;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;

interface UrlSetInterface
{
    const ROOT_NODE_ELEMENT = 'urlset';
    const ROOT_NODE_XMLNS = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    const FILE_SIZE_LIMIT = 10485760; // 10 MB
    const URLS_LIMIT = 50000;

    /**
     * @return UrlItemInterface[]
     */
    public function getUrlItems();

    /**
     * @param UrlItemInterface $urlItem
     * @return bool
     */
    public function addUrlItem(UrlItemInterface $urlItem);
}
