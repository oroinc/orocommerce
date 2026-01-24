<?php

namespace Oro\Component\WebCatalog\Entity;

/**
 * Defines the contract for entities that are associated with a web catalog.
 *
 * Implementing classes represent entities that belong to a specific web catalog,
 * allowing them to be organized and managed within the web catalog structure.
 */
interface WebCatalogAwareInterface
{
    /**
     * @return WebCatalogInterface
     */
    public function getWebCatalog();
}
