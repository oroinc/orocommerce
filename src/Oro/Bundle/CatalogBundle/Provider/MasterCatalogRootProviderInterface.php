<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;

/**
 * Provides master catalog root.
 */
interface MasterCatalogRootProviderInterface
{
    /**
     * @return Category
     */
    public function getMasterCatalogRoot(): Category;
}
