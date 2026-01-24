<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

/**
 * Defines the contract for determining visibility of category default product unit options.
 */
interface CategoryDefaultProductUnitOptionsVisibilityInterface
{
    /**
     * @return bool
     */
    public function isDefaultUnitPrecisionSelectionAvailable();
}
