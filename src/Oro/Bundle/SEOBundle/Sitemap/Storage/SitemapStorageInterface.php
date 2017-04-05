<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Storage;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;

interface SitemapStorageInterface
{
    /**
     * Add $urlItem to the storage.
     * Returns true if operation is successful, returns false otherwise if limits have been reached.
     *
     * @param UrlItemInterface $urlItem
     * @return bool
     */
    public function addUrlItem(UrlItemInterface $urlItem);

    /**
     * Returns formatted urls sitemap xml.
     *
     * @return string
     */
    public function getContents();

    /**
     * @return integer
     */
    public function getUrlItemsCount();
}
