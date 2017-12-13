<?php

namespace Oro\Component\WebCatalog\Provider;

use Oro\Component\WebCatalog\Entity\WebCatalogInterface;

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
