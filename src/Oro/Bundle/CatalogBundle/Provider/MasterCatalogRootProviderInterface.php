<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;

/**
 * Provides master catalog root.
 */
interface MasterCatalogRootProviderInterface
{
    public function getMasterCatalogRoot(): Category;
}
