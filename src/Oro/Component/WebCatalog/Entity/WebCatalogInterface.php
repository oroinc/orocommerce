<?php

namespace Oro\Component\WebCatalog\Entity;

/**
 * Defines the contract for web catalogs.
 *
 * A web catalog is a hierarchical structure of content nodes that organize website content.
 * It provides the foundation for managing pages, categories, and other content within the system.
 */
interface WebCatalogInterface
{
    /**
     * @return int
     */
    public function getId();
}
