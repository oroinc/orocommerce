<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Storage;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;

/**
 * Defines the contract for sitemap storage implementations.
 *
 * This interface specifies the methods that sitemap storage implementations must provide for adding URL items,
 * retrieving formatted sitemap content, and tracking the number of items stored. Implementations can use
 * different storage formats (e.g., XML) and handle size limits as needed.
 */
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
