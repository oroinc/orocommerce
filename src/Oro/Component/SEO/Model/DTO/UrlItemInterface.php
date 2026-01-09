<?php

namespace Oro\Component\SEO\Model\DTO;

/**
 * Defines the contract for URL items used in sitemap generation.
 *
 * URL items represent individual entries in a sitemap, including the location (URL),
 * change frequency, priority, and last modification date. These properties are used
 * to generate XML sitemaps for search engine optimization.
 */
interface UrlItemInterface
{
    /**
     * @return string
     */
    public function getLocation();

    /**
     * @return null|string
     */
    public function getChangeFrequency();

    /**
     * @return null|float
     */
    public function getPriority();

    /**
     * @return null|string
     */
    public function getLastModification();
}
