<?php

namespace Oro\Component\WebCatalog\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * Defines the contract for content nodes in the web catalog hierarchy.
 *
 * Content nodes represent pages or sections in the web catalog structure. Each node has
 * an ID, a collection of content variants (different representations for different contexts),
 * localized titles, and a flag indicating whether variant titles should override node titles.
 */
interface ContentNodeInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return ContentVariantInterface[]
     */
    public function getContentVariants();

    /**
     * @return Collection
     */
    public function getTitles();

    /**
     * @return boolean
     */
    public function isRewriteVariantTitle();
}
