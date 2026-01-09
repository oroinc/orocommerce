<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

/**
 * Basic implementation of category default product unit options visibility.
 *
 * Provides a simple implementation that always enables default unit precision selection,
 * allowing categories to configure default product unit and precision settings.
 */
class BasicCategoryDefaultProductUnitOptionsVisibility implements CategoryDefaultProductUnitOptionsVisibilityInterface
{
    #[\Override]
    public function isDefaultUnitPrecisionSelectionAvailable()
    {
        return true;
    }
}
