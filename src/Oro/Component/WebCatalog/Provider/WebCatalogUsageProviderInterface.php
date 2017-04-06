<?php

namespace Oro\Component\WebCatalog\Provider;

use Oro\Component\WebCatalog\Entity\WebCatalogInterface;

interface WebCatalogUsageProviderInterface
{
    /**
     * @param WebCatalogInterface $webCatalog
     * @return bool
     */
    public function isInUse(WebCatalogInterface $webCatalog);

    /**
     * @param array $entities
     * @return array Format: [assignedEntityId => webCatalogId, ...]
     */
    public function getAssignedWebCatalogs(array $entities = []);
}
