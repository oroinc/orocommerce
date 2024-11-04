<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

class BasicCategoryDefaultProductUnitOptionsVisibility implements CategoryDefaultProductUnitOptionsVisibilityInterface
{
    #[\Override]
    public function isDefaultUnitPrecisionSelectionAvailable()
    {
        return true;
    }
}
