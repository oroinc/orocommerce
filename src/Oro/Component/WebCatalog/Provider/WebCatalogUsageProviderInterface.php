<?php

namespace Oro\Component\WebCatalog\Provider;

use Oro\Component\WebCatalog\Entity\WebCatalogInterface;

/**
 * Defines the contract for providers that track web catalog usage and assignments.
 *
 * Implementations determine whether a web catalog is currently in use and provide
 * information about which web catalogs are assigned to which websites.
 */
interface WebCatalogUsageProviderInterface
{
    /**
     * @param WebCatalogInterface $webCatalog
     *
     * @return bool
     */
    public function isInUse(WebCatalogInterface $webCatalog);

    /**
     * @return array [web site id => web catalog id, ...]
     */
    public function getAssignedWebCatalogs();
}
